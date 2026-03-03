<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Inventory\Enums\MovementType;
use Illuminate\Validation\Rules\Enum;

class StoreMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Autorización manejada por policies
    }

    public function rules(): array
    {
        return [
            'type' => ['required', new Enum(MovementType::class)],
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'from_warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'to_warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'serial_id' => ['nullable', 'exists:serials,id'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $type = MovementType::tryFrom($this->input('type'));

            if (!$type) {
                return;
            }

            // Validar según tipo de movimiento
            if ($type === MovementType::TRANSFER) {
                if (!$this->input('from_warehouse_id') || !$this->input('to_warehouse_id')) {
                    $validator->errors()->add(
                        'warehouses',
                        'Las transferencias requieren almacén origen y destino'
                    );
                }

                if ($this->input('from_warehouse_id') === $this->input('to_warehouse_id')) {
                    $validator->errors()->add(
                        'warehouses',
                        'El almacén origen y destino no pueden ser el mismo'
                    );
                }
            }

            if ($type->isIncoming() && !$this->input('to_warehouse_id')) {
                $validator->errors()->add('to_warehouse_id', 'Se requiere almacén de destino');
            }

            if ($type->isOutgoing() && !$this->input('from_warehouse_id')) {
                $validator->errors()->add('from_warehouse_id', 'Se requiere almacén de origen');
            }
        });
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Debe seleccionar un producto',
            'product_id.exists' => 'El producto seleccionado no existe',
            'quantity.required' => 'La cantidad es obligatoria',
            'quantity.min' => 'La cantidad debe ser mayor a 0',
        ];
    }
}
