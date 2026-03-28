<?php

declare(strict_types=1);

namespace Modules\Workflow\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\AccessControl\Entities\Role;
use Modules\Workflow\Entities\Place;
use Modules\Workflow\Entities\Transition;
use Modules\Workflow\Entities\TransitionPermission;
use Modules\Workflow\Entities\WorkflowDefinition;

class WorkflowDefinitionSeeder extends Seeder
{
    public function run(): void
    {
        $this->createSupportTicketWorkflow();
        $this->createServiceCancellationWorkflow();
    }

    protected function createInstallationWorkflow(): void
    {
        $workflow = WorkflowDefinition::create([
            'code' => 'installation',
            'name' => 'Instalación de Servicio',
            'description' => 'Flujo completo para la instalación de un nuevo servicio',
            'entity_type' => 'Modules\\Subscription\\Entities\\Subscription',
            'is_active' => true,
        ]);

        // Crear lugares (estados)
        $places = [
            ['code' => 'draft', 'name' => 'Borrador', 'color' => '#6B7280', 'is_initial' => true, 'order' => 1],
            ['code' => 'pending_schedule', 'name' => 'Pendiente Agendar', 'color' => '#F59E0B', 'order' => 2],
            ['code' => 'scheduled', 'name' => 'Agendado', 'color' => '#3B82F6', 'order' => 3],
            ['code' => 'assigned', 'name' => 'Asignado', 'color' => '#8B5CF6', 'order' => 4],
            ['code' => 'in_transit', 'name' => 'En Tránsito', 'color' => '#EC4899', 'order' => 5],
            ['code' => 'on_site', 'name' => 'En Sitio', 'color' => '#06B6D4', 'order' => 6],
            ['code' => 'in_progress', 'name' => 'En Progreso', 'color' => '#10B981', 'order' => 7],
            ['code' => 'pending_validation', 'name' => 'Pendiente Validación', 'color' => '#F97316', 'order' => 8],
            ['code' => 'completed', 'name' => 'Completado', 'color' => '#22C55E', 'is_final' => true, 'order' => 9],
            ['code' => 'cancelled', 'name' => 'Cancelado', 'color' => '#EF4444', 'is_final' => true, 'order' => 10],
            ['code' => 'rescheduled', 'name' => 'Reagendado', 'color' => '#A855F7', 'order' => 11],
        ];

        $placeModels = [];
        foreach ($places as $placeData) {
            $placeData['workflow_id'] = $workflow->id;
            $placeModels[$placeData['code']] = Place::create($placeData);
        }

        // Crear transiciones
        $transitions = [
            ['code' => 'submit', 'name' => 'Enviar', 'from' => 'draft', 'to' => 'pending_schedule', 'roles' => ['sales', 'admin', 'supervisor']],
            ['code' => 'schedule', 'name' => 'Agendar', 'from' => 'pending_schedule', 'to' => 'scheduled', 'roles' => ['coordinator', 'admin', 'supervisor']],
            ['code' => 'assign', 'name' => 'Asignar Técnico', 'from' => 'scheduled', 'to' => 'assigned', 'roles' => ['coordinator', 'admin', 'supervisor']],
            ['code' => 'start_transit', 'name' => 'Iniciar Tránsito', 'from' => 'assigned', 'to' => 'in_transit', 'roles' => ['technician']],
            ['code' => 'arrive', 'name' => 'Llegar al Sitio', 'from' => 'in_transit', 'to' => 'on_site', 'roles' => ['technician']],
            ['code' => 'start_work', 'name' => 'Iniciar Trabajo', 'from' => 'on_site', 'to' => 'in_progress', 'roles' => ['technician']],
            ['code' => 'submit_completion', 'name' => 'Enviar a Validación', 'from' => 'in_progress', 'to' => 'pending_validation', 'roles' => ['technician']],
            ['code' => 'approve', 'name' => 'Aprobar', 'from' => 'pending_validation', 'to' => 'completed', 'roles' => ['supervisor', 'admin']],
            ['code' => 'reject', 'name' => 'Rechazar', 'from' => 'pending_validation', 'to' => 'in_progress', 'roles' => ['supervisor', 'admin']],
            ['code' => 'cancel', 'name' => 'Cancelar', 'from' => null, 'to' => 'cancelled', 'from_any' => true, 'roles' => ['admin', 'supervisor']],
            ['code' => 'reschedule_from_scheduled', 'name' => 'Reagendar', 'from' => 'scheduled', 'to' => 'rescheduled', 'roles' => ['coordinator', 'admin', 'supervisor']],
            ['code' => 'reschedule_from_assigned', 'name' => 'Reagendar', 'from' => 'assigned', 'to' => 'rescheduled', 'roles' => ['coordinator', 'admin', 'supervisor']],
            ['code' => 'schedule_again', 'name' => 'Programar Nuevamente', 'from' => 'rescheduled', 'to' => 'scheduled', 'roles' => ['coordinator', 'admin', 'supervisor']],
        ];

        foreach ($transitions as $transitionData) {
            $transition = Transition::create([
                'workflow_id' => $workflow->id,
                'from_place_id' => isset($transitionData['from']) ? $placeModels[$transitionData['from']]->id : null,
                'to_place_id' => $placeModels[$transitionData['to']]->id,
                'code' => $transitionData['code'],
                'name' => $transitionData['name'],
                'from_any' => $transitionData['from_any'] ?? false,
            ]);

            $this->attachRoles($transition, $transitionData['roles']);
        }
    }

    protected function createSupportTicketWorkflow(): void
    {
        $workflow = WorkflowDefinition::create([
            'code' => 'support_ticket',
            'name' => 'Ticket de Soporte',
            'description' => 'Flujo para gestión de tickets de soporte técnico',
            'entity_type' => 'Modules\\FieldOps\\Entities\\WorkOrder',
            'is_active' => true,
        ]);

        $places = [
            ['code' => 'open', 'name' => 'Abierto', 'color' => '#3B82F6', 'is_initial' => true, 'order' => 1],
            ['code' => 'assigned', 'name' => 'Asignado', 'color' => '#8B5CF6', 'order' => 2],
            ['code' => 'in_progress', 'name' => 'En Progreso', 'color' => '#F59E0B', 'order' => 3],
            ['code' => 'waiting_customer', 'name' => 'Esperando Cliente', 'color' => '#06B6D4', 'order' => 4],
            ['code' => 'waiting_parts', 'name' => 'Esperando Repuestos', 'color' => '#EC4899', 'order' => 5],
            ['code' => 'resolved', 'name' => 'Resuelto', 'color' => '#10B981', 'order' => 6],
            ['code' => 'closed', 'name' => 'Cerrado', 'color' => '#22C55E', 'is_final' => true, 'order' => 7],
        ];

        $placeModels = [];
        foreach ($places as $placeData) {
            $placeData['workflow_id'] = $workflow->id;
            $placeModels[$placeData['code']] = Place::create($placeData);
        }

        $transitions = [
            ['code' => 'assign', 'name' => 'Asignar', 'from' => 'open', 'to' => 'assigned', 'roles' => ['support', 'supervisor', 'admin']],
            ['code' => 'start', 'name' => 'Iniciar', 'from' => 'assigned', 'to' => 'in_progress', 'roles' => ['technician', 'support']],
            ['code' => 'wait_customer', 'name' => 'Esperar Cliente', 'from' => 'in_progress', 'to' => 'waiting_customer', 'roles' => ['technician', 'support']],
            ['code' => 'wait_parts', 'name' => 'Esperar Repuestos', 'from' => 'in_progress', 'to' => 'waiting_parts', 'roles' => ['technician', 'support']],
            ['code' => 'resume_from_customer', 'name' => 'Reanudar', 'from' => 'waiting_customer', 'to' => 'in_progress', 'roles' => ['technician', 'support']],
            ['code' => 'resume_from_parts', 'name' => 'Reanudar', 'from' => 'waiting_parts', 'to' => 'in_progress', 'roles' => ['technician', 'support']],
            ['code' => 'resolve', 'name' => 'Resolver', 'from' => 'in_progress', 'to' => 'resolved', 'roles' => ['technician', 'support']],
            ['code' => 'close', 'name' => 'Cerrar', 'from' => 'resolved', 'to' => 'closed', 'roles' => ['support', 'supervisor', 'admin']],
            ['code' => 'reopen', 'name' => 'Reabrir', 'from' => 'resolved', 'to' => 'in_progress', 'roles' => ['support', 'supervisor', 'admin']],
        ];

        foreach ($transitions as $transitionData) {
            $transition = Transition::create([
                'workflow_id' => $workflow->id,
                'from_place_id' => $placeModels[$transitionData['from']]->id,
                'to_place_id' => $placeModels[$transitionData['to']]->id,
                'code' => $transitionData['code'],
                'name' => $transitionData['name'],
            ]);

            $this->attachRoles($transition, $transitionData['roles']);
        }
    }

    protected function createServiceCancellationWorkflow(): void
    {
        $workflow = WorkflowDefinition::create([
            'code' => 'service_cancellation',
            'name' => 'Baja de Servicio',
            'description' => 'Flujo para gestión de cancelación de servicios',
            'entity_type' => 'Modules\\Subscription\\Entities\\Subscription',
            'is_active' => true,
        ]);

        $places = [
            ['code' => 'requested', 'name' => 'Solicitado', 'color' => '#3B82F6', 'is_initial' => true, 'order' => 1],
            ['code' => 'pending_equipment_return', 'name' => 'Pendiente Devolución Equipo', 'color' => '#F59E0B', 'order' => 2],
            ['code' => 'pending_final_invoice', 'name' => 'Pendiente Factura Final', 'color' => '#EC4899', 'order' => 3],
            ['code' => 'completed', 'name' => 'Completado', 'color' => '#22C55E', 'is_final' => true, 'order' => 4],
        ];

        $placeModels = [];
        foreach ($places as $placeData) {
            $placeData['workflow_id'] = $workflow->id;
            $placeModels[$placeData['code']] = Place::create($placeData);
        }

        $transitions = [
            ['code' => 'schedule_return', 'name' => 'Programar Devolución', 'from' => 'requested', 'to' => 'pending_equipment_return', 'roles' => ['support', 'coordinator', 'admin']],
            ['code' => 'confirm_return', 'name' => 'Confirmar Devolución', 'from' => 'pending_equipment_return', 'to' => 'pending_final_invoice', 'roles' => ['technician', 'support', 'admin']],
            ['code' => 'generate_invoice', 'name' => 'Generar Factura Final', 'from' => 'pending_final_invoice', 'to' => 'completed', 'roles' => ['billing', 'admin']],
            ['code' => 'skip_equipment', 'name' => 'Sin Equipo a Devolver', 'from' => 'requested', 'to' => 'pending_final_invoice', 'roles' => ['supervisor', 'admin']],
        ];

        foreach ($transitions as $transitionData) {
            $transition = Transition::create([
                'workflow_id' => $workflow->id,
                'from_place_id' => $placeModels[$transitionData['from']]->id,
                'to_place_id' => $placeModels[$transitionData['to']]->id,
                'code' => $transitionData['code'],
                'name' => $transitionData['name'],
            ]);

            $this->attachRoles($transition, $transitionData['roles']);
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
