<?php

declare(strict_types=1);

namespace Modules\Workflow\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\AccessControl\Entities\Role;
use Modules\FieldOps\app\SideEffects\CompleteInstallationAction;
use Modules\FieldOps\app\SideEffects\CreateAppointmentAction;
use Modules\FieldOps\app\SideEffects\ReserveMaterialsAction;
use Modules\FieldOps\app\SideEffects\SubmitValidationAction;
use Modules\FieldOps\app\SideEffects\ValidateGeofenceAction;
use Modules\Workflow\Entities\Place;
use Modules\Workflow\Entities\SideEffect;
use Modules\Workflow\Entities\Transition;
use Modules\Workflow\Entities\TransitionPermission;
use Modules\Workflow\Entities\WorkflowDefinition;
use Modules\Workflow\Enums\TriggerPoint;

class InstallationWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $workflow = WorkflowDefinition::updateOrCreate(
            ['code' => 'installation'],
            [
                'name' => 'Instalacion de Servicio',
                'description' => 'Workflow operacional para ordenes de instalacion',
                'entity_type' => 'Modules\\FieldOps\\app\\Models\\WorkOrder',
                'is_active' => true,
            ]
        );

        $workflow->transitions()->delete();
        $workflow->places()->delete();

        $places = $this->createPlaces($workflow->id);
        $transitions = $this->createTransitions($workflow->id, $places);

        $this->createSideEffects($transitions);
    }

    protected function createPlaces(int $workflowId): array
    {
        $definitions = [
            ['code' => 'pending_schedule', 'name' => 'Pendiente Agendar', 'color' => '#F59E0B', 'is_initial' => true, 'order' => 1],
            ['code' => 'scheduled', 'name' => 'Agendado', 'color' => '#3B82F6', 'order' => 2],
            ['code' => 'assigned', 'name' => 'Asignado', 'color' => '#8B5CF6', 'order' => 3],
            ['code' => 'materials_reserved', 'name' => 'Materiales Reservados', 'color' => '#A855F7', 'order' => 4],
            ['code' => 'in_transit', 'name' => 'En Transito', 'color' => '#EC4899', 'order' => 5],
            ['code' => 'on_site', 'name' => 'En Sitio', 'color' => '#06B6D4', 'order' => 6],
            ['code' => 'in_progress', 'name' => 'En Progreso', 'color' => '#10B981', 'order' => 7],
            ['code' => 'pending_validation', 'name' => 'Pendiente Validacion', 'color' => '#F97316', 'order' => 8],
            ['code' => 'completed', 'name' => 'Completado', 'color' => '#22C55E', 'is_final' => true, 'order' => 9],
            ['code' => 'cancelled', 'name' => 'Cancelado', 'color' => '#EF4444', 'is_final' => true, 'order' => 10],
            ['code' => 'failed', 'name' => 'Fallido', 'color' => '#991B1B', 'is_final' => true, 'order' => 11],
        ];

        $places = [];

        foreach ($definitions as $definition) {
            $places[$definition['code']] = Place::create([
                'workflow_id' => $workflowId,
                ...$definition,
            ]);
        }

        return $places;
    }

    protected function createTransitions(int $workflowId, array $places): array
    {
        $definitions = [
            ['code' => 'schedule', 'name' => 'Agendar', 'from' => 'pending_schedule', 'to' => 'scheduled', 'roles' => ['sales', 'support', 'supervisor', 'admin']],
            ['code' => 'assign', 'name' => 'Asignar Tecnico', 'from' => 'scheduled', 'to' => 'assigned', 'roles' => ['support', 'supervisor', 'admin']],
            ['code' => 'reserve_materials', 'name' => 'Reservar Materiales', 'from' => 'assigned', 'to' => 'materials_reserved', 'roles' => ['support', 'supervisor', 'admin']],
            ['code' => 'start_transit', 'name' => 'Iniciar Transito', 'from' => 'materials_reserved', 'to' => 'in_transit', 'roles' => ['technician']],
            ['code' => 'arrive', 'name' => 'Llegar al Sitio', 'from' => 'in_transit', 'to' => 'on_site', 'roles' => ['technician']],
            ['code' => 'start_work', 'name' => 'Iniciar Trabajo', 'from' => 'on_site', 'to' => 'in_progress', 'roles' => ['technician']],
            ['code' => 'submit_validation', 'name' => 'Enviar a Validacion', 'from' => 'in_progress', 'to' => 'pending_validation', 'roles' => ['technician']],
            ['code' => 'approve', 'name' => 'Aprobar', 'from' => 'pending_validation', 'to' => 'completed', 'roles' => ['supervisor', 'admin']],
            ['code' => 'reject', 'name' => 'Rechazar', 'from' => 'pending_validation', 'to' => 'in_progress', 'roles' => ['supervisor', 'admin']],
            ['code' => 'cancel', 'name' => 'Cancelar', 'to' => 'cancelled', 'from_any' => true, 'roles' => ['support', 'supervisor', 'admin']],
            ['code' => 'mark_failed', 'name' => 'Marcar Fallido', 'to' => 'failed', 'from_any' => true, 'roles' => ['support', 'supervisor', 'admin']],
        ];

        $transitions = [];

        foreach ($definitions as $definition) {
            $transition = Transition::create([
                'workflow_id' => $workflowId,
                'from_place_id' => isset($definition['from']) ? $places[$definition['from']]->id : null,
                'to_place_id' => $places[$definition['to']]->id,
                'code' => $definition['code'],
                'name' => $definition['name'],
                'from_any' => $definition['from_any'] ?? false,
            ]);

            $this->attachRoles($transition, $definition['roles']);
            $transitions[$definition['code']] = $transition;
        }

        return $transitions;
    }

    protected function createSideEffects(array $transitions): void
    {
        $definitions = [
            'assign' => [
                [
                    'trigger_point' => TriggerPoint::AFTER,
                    'action_class' => CreateAppointmentAction::class,
                    'parameters' => [],
                    'order' => 10,
                ],
            ],
            'reserve_materials' => [
                [
                    'trigger_point' => TriggerPoint::AFTER,
                    'action_class' => ReserveMaterialsAction::class,
                    'parameters' => [],
                    'order' => 10,
                ],
            ],
            'arrive' => [
                [
                    'trigger_point' => TriggerPoint::BEFORE,
                    'action_class' => ValidateGeofenceAction::class,
                    'parameters' => ['radius_meters' => 100],
                    'order' => 10,
                ],
            ],
            'submit_validation' => [
                [
                    'trigger_point' => TriggerPoint::BEFORE,
                    'action_class' => SubmitValidationAction::class,
                    'parameters' => [
                        'required' => ['checklist_completed', 'required_photos', 'technician_location', 'work_started', 'work_completed'],
                    ],
                    'order' => 10,
                ],
            ],
            'approve' => [
                [
                    'trigger_point' => TriggerPoint::AFTER,
                    'action_class' => CompleteInstallationAction::class,
                    'parameters' => [
                        'required' => ['checklist_completed', 'required_photos', 'technician_location', 'work_started', 'work_completed'],
                        'generate_initial_invoice' => true,
                    ],
                    'order' => 10,
                ],
            ],
        ];

        foreach ($definitions as $transitionCode => $sideEffects) {
            foreach ($sideEffects as $definition) {
                SideEffect::create([
                    'transition_id' => $transitions[$transitionCode]->id,
                    'trigger_point' => $definition['trigger_point'],
                    'action_class' => $definition['action_class'],
                    'parameters' => $definition['parameters'],
                    'order' => $definition['order'],
                    'is_active' => true,
                ]);
            }
        }
    }

    protected function attachRoles(Transition $transition, array $roleCodes): void
    {
        $roles = Role::whereIn('code', $roleCodes)->get();

        foreach ($roles as $role) {
            TransitionPermission::create([
                'transition_id' => $transition->id,
                'role_id' => $role->id,
            ]);
        }
    }
}
