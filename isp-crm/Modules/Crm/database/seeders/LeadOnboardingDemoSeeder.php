<?php

declare(strict_types=1);

namespace Modules\Crm\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\AccessControl\Entities\User;
use Modules\AccessControl\Entities\Zone;
use Modules\Crm\Entities\Address;
use Modules\Crm\Entities\CapacityReservation;
use Modules\Crm\Entities\Contact;
use Modules\Crm\Entities\Customer;
use Modules\Crm\Entities\FeasibilityRequest;
use Modules\Crm\Entities\Lead;
use Modules\Crm\Enums\AddressType;
use Modules\Crm\Enums\ContactType;
use Modules\Crm\Enums\CustomerType;
use Modules\Crm\Enums\DocumentType;
use Modules\Crm\Enums\LeadSource;
use Modules\Crm\Enums\LeadStatus;
use Modules\Network\Entities\NapBox;
use Modules\Network\Entities\NapPort;
use Modules\Network\Entities\Node;
use Modules\Network\Enums\NapPortStatus;

class LeadOnboardingDemoSeeder extends Seeder
{
    public function run(): void
    {
        $feasibilityRequestedAt = now()->startOfDay()->addHours(10);
        $feasibilityResolvedAt = $feasibilityRequestedAt->copy()->addMinutes(3);
        $reservationExpiresAt = now()->startOfDay()->addDay()->addHours(12);

        $mainZone = $this->upsertZone([
            'code' => 'MAIN',
            'name' => 'Zona Principal',
            'parent_id' => null,
            'description' => 'Zona base para pruebas de onboarding',
            'is_active' => true,
        ]);

        $northZone = $this->upsertZone([
            'code' => 'LIMA-NORTE',
            'name' => 'Lima Norte',
            'parent_id' => $mainZone->id,
            'description' => 'Zona operativa de pruebas para alta comercial',
            'is_active' => true,
        ]);

        $salesAdvisor = $this->upsertUser([
            'name' => 'Asesora Demo CRM',
            'email' => 'crm.demo@noretel.test',
            'phone' => '900111222',
            'zone_id' => $northZone->id,
        ]);

        $technicalAdvisor = $this->upsertUser([
            'name' => 'Factibilidad Demo CRM',
            'email' => 'factibilidad.demo@noretel.test',
            'phone' => '900111333',
            'zone_id' => $northZone->id,
        ]);

        $popNode = $this->upsertNode([
            'code' => 'POP-DEMO-001',
            'name' => 'POP Demo Lima Norte',
            'type' => 'pop',
            'address' => 'Av. Demo 120, Lima Norte',
            'latitude' => -12.0195000,
            'longitude' => -77.0505000,
            'altitude' => 18.50,
            'status' => 'active',
            'description' => 'Nodo POP para pruebas del flujo comercial',
            'commissioned_at' => now()->subMonths(10),
        ]);

        $towerNode = $this->upsertNode([
            'code' => 'TWR-DEMO-001',
            'name' => 'Torre Demo Lima Norte',
            'type' => 'tower',
            'address' => 'Jr. Demo 450, Lima Norte',
            'latitude' => -12.0202000,
            'longitude' => -77.0498000,
            'altitude' => 32.00,
            'status' => 'active',
            'description' => 'Nodo de acceso usado para evaluar cobertura y NAPs',
            'commissioned_at' => now()->subMonths(8),
        ]);

        $napPrimary = $this->upsertNapBox([
            'node_id' => $towerNode->id,
            'code' => 'NAP-DEMO-001',
            'name' => 'NAP Demo Principal',
            'type' => 'splitter_1x4',
            'latitude' => -12.0206500,
            'longitude' => -77.0494500,
            'address' => 'Calle Los Robles 101, Lima Norte',
            'total_ports' => 4,
            'status' => 'active',
            'installed_at' => now()->subMonths(6),
            'notes' => 'NAP principal para pruebas de factibilidad',
        ]);

        $napBackup = $this->upsertNapBox([
            'node_id' => $popNode->id,
            'code' => 'NAP-DEMO-002',
            'name' => 'NAP Demo Respaldo',
            'type' => 'splitter_1x4',
            'latitude' => -12.0191000,
            'longitude' => -77.0512000,
            'address' => 'Av. Los Ficus 220, Lima Norte',
            'total_ports' => 4,
            'status' => 'active',
            'installed_at' => now()->subMonths(5),
            'notes' => 'NAP secundario para comparar cobertura',
        ]);

        $this->syncNapPorts($napPrimary, [
            1 => ['status' => NapPortStatus::RESERVED->value, 'label' => 'LEAD-DEMO-002', 'notes' => 'Reserva demo activa'],
            2 => ['status' => NapPortStatus::FREE->value, 'label' => null, 'notes' => 'Puerto libre para pruebas manuales'],
            3 => ['status' => NapPortStatus::OCCUPIED->value, 'label' => 'SUB-DEMO-001', 'notes' => 'Puerto ocupado de ejemplo'],
            4 => ['status' => NapPortStatus::DAMAGED->value, 'label' => null, 'notes' => 'Puerto danado para pruebas de validacion'],
        ]);

        $this->syncNapPorts($napBackup, [
            1 => ['status' => NapPortStatus::FREE->value, 'label' => null, 'notes' => 'Puerto libre de respaldo'],
            2 => ['status' => NapPortStatus::FREE->value, 'label' => null, 'notes' => 'Puerto libre de respaldo'],
            3 => ['status' => NapPortStatus::OCCUPIED->value, 'label' => 'SUB-DEMO-002', 'notes' => 'Puerto ocupado de respaldo'],
            4 => ['status' => NapPortStatus::FREE->value, 'label' => null, 'notes' => 'Puerto libre para crecimiento'],
        ]);

        $leadReady = $this->upsertLead(
            ['email' => 'lead.ready@noretel.test'],
            [
                'name' => 'Juan Perez Onboarding',
                'document_type' => DocumentType::DNI->value,
                'document_number' => '73214568',
                'phone' => '987654321',
                'email' => 'lead.ready@noretel.test',
                'source' => LeadSource::WEBSITE->value,
                'status' => LeadStatus::QUALIFIED->value,
                'is_duplicate' => false,
                'duplicate_of_id' => null,
                'duplicate_resolution' => null,
                'notes' => 'Lead limpio para probar onboarding completo desde cero.',
                'zone_id' => $northZone->id,
                'assigned_to' => $salesAdvisor->id,
                'created_by' => $salesAdvisor->id,
                'converted_at' => null,
            ]
        );

        $leadInProgress = $this->upsertLead(
            ['email' => 'lead.factibilidad@noretel.test'],
            [
                'name' => 'Maria Quispe Factibilidad',
                'document_type' => DocumentType::DNI->value,
                'document_number' => '74125896',
                'phone' => '987654322',
                'email' => 'lead.factibilidad@noretel.test',
                'source' => LeadSource::PHONE->value,
                'status' => LeadStatus::QUALIFIED->value,
                'is_duplicate' => false,
                'duplicate_of_id' => null,
                'duplicate_resolution' => null,
                'notes' => 'Lead con factibilidad confirmada y reserva activa para validar la segunda mitad del flujo.',
                'zone_id' => $northZone->id,
                'assigned_to' => $technicalAdvisor->id,
                'created_by' => $salesAdvisor->id,
                'converted_at' => null,
            ]
        );

        $leadConverted = $this->upsertLead(
            ['email' => 'lead.convertido@noretel.test'],
            [
                'name' => 'Carlos Ramos Convertido',
                'document_type' => DocumentType::DNI->value,
                'document_number' => '45678912',
                'phone' => '987654323',
                'email' => 'lead.convertido@noretel.test',
                'source' => LeadSource::REFERRAL->value,
                'status' => LeadStatus::WON->value,
                'is_duplicate' => false,
                'duplicate_of_id' => null,
                'duplicate_resolution' => 'converted_to_customer',
                'notes' => 'Lead ya convertido para validar el tramo final del proceso.',
                'zone_id' => $northZone->id,
                'assigned_to' => $salesAdvisor->id,
                'created_by' => $salesAdvisor->id,
                'converted_at' => now()->subDays(12),
            ]
        );

        $leadDuplicate = $this->upsertLead(
            ['email' => 'lead.duplicado@noretel.test'],
            [
                'name' => 'Juan Perez Duplicado',
                'document_type' => DocumentType::DNI->value,
                'document_number' => '73214568',
                'phone' => '987654321',
                'email' => 'lead.duplicado@noretel.test',
                'source' => LeadSource::SOCIAL_MEDIA->value,
                'status' => LeadStatus::NEW->value,
                'is_duplicate' => true,
                'duplicate_of_id' => $leadReady->id,
                'duplicate_resolution' => null,
                'notes' => 'Lead marcado como duplicado del lead listo para onboarding.',
                'zone_id' => $northZone->id,
                'assigned_to' => $salesAdvisor->id,
                'created_by' => $salesAdvisor->id,
                'converted_at' => null,
            ]
        );

        $leadDuplicate->forceFill([
            'is_duplicate' => true,
            'duplicate_of_id' => $leadReady->id,
            'duplicate_resolution' => null,
        ])->save();

        $customer = Customer::updateOrCreate(
            [
                'document_type' => DocumentType::DNI->value,
                'document_number' => '45678912',
            ],
            [
                'lead_id' => $leadConverted->id,
                'customer_type' => CustomerType::PERSONAL->value,
                'name' => 'Carlos Ramos Convertido',
                'trade_name' => null,
                'phone' => '987654323',
                'email' => 'cliente.demo@noretel.test',
                'billing_email' => 'cobranza.demo@noretel.test',
                'is_active' => true,
                'credit_limit' => 0,
                'tax_exempt' => false,
                'created_by' => $salesAdvisor->id,
            ]
        );

        Address::updateOrCreate(
            [
                'customer_id' => $customer->id,
                'type' => AddressType::SERVICE->value,
                'label' => 'Casa',
            ],
            [
                'street' => 'Calle Los Robles',
                'number' => '145',
                'floor' => null,
                'apartment' => null,
                'reference' => 'Frente al parque central',
                'address_reference' => 'Porton negro y fachada crema',
                'photo_url' => null,
                'district' => 'Independencia',
                'city' => 'Lima',
                'province' => 'Lima',
                'postal_code' => '15311',
                'latitude' => -12.0207200,
                'longitude' => -77.0495100,
                'georeference_quality' => 'verified',
                'zone_id' => $northZone->id,
                'is_default' => true,
            ]
        );

        Address::updateOrCreate(
            [
                'customer_id' => $customer->id,
                'type' => AddressType::BILLING->value,
                'label' => 'Facturacion',
            ],
            [
                'street' => 'Av. Tomasa Valle',
                'number' => '880',
                'floor' => '3',
                'apartment' => '302',
                'reference' => 'Edificio gris, recepcion primer piso',
                'address_reference' => 'Usar intercomunicador',
                'photo_url' => null,
                'district' => 'Los Olivos',
                'city' => 'Lima',
                'province' => 'Lima',
                'postal_code' => '15302',
                'latitude' => -12.0129500,
                'longitude' => -77.0582100,
                'georeference_quality' => 'approximate',
                'zone_id' => $northZone->id,
                'is_default' => true,
            ]
        );

        Contact::updateOrCreate(
            [
                'customer_id' => $customer->id,
                'type' => ContactType::PHONE->value,
                'value' => '987654323',
            ],
            [
                'name' => 'Carlos Ramos',
                'relationship' => 'Titular',
                'is_primary' => true,
                'receives_notifications' => true,
            ]
        );

        Contact::updateOrCreate(
            [
                'customer_id' => $customer->id,
                'type' => ContactType::EMAIL->value,
                'value' => 'cliente.demo@noretel.test',
            ],
            [
                'name' => 'Carlos Ramos',
                'relationship' => 'Titular',
                'is_primary' => false,
                'receives_notifications' => true,
            ]
        );

        $reservedPort = NapPort::where('nap_box_id', $napPrimary->id)
            ->where('port_number', 1)
            ->firstOrFail();

        $feasibilityRequest = FeasibilityRequest::updateOrCreate(
            [
                'lead_id' => $leadInProgress->id,
                'status' => 'confirmed',
            ],
            [
                'address_id' => null,
                'status' => 'confirmed',
                'latitude' => -12.0207100,
                'longitude' => -77.0495400,
                'radius_meters' => 500,
                'result_data' => [
                    'is_feasible' => true,
                    'nearest_nap' => [
                        'id' => $napPrimary->id,
                        'code' => $napPrimary->code,
                        'name' => $napPrimary->name,
                        'free_ports' => 1,
                    ],
                    'distance_meters' => 28.4,
                    'reason' => null,
                    'available_naps_count' => 2,
                ],
                'requested_at' => $feasibilityRequestedAt,
                'resolved_at' => $feasibilityResolvedAt,
            ]
        );

        CapacityReservation::updateOrCreate(
            [
                'reservable_type' => NapPort::class,
                'reservable_id' => $reservedPort->id,
                'lead_id' => $leadInProgress->id,
            ],
            [
                'feasibility_request_id' => $feasibilityRequest->id,
                'status' => 'active',
                'metadata' => [
                    'nap_box_id' => $napPrimary->id,
                    'nap_box_code' => $napPrimary->code,
                    'port_number' => $reservedPort->port_number,
                ],
                'expires_at' => $reservationExpiresAt,
                'released_at' => null,
            ]
        );

        $this->command?->info('LeadOnboardingDemoSeeder ejecutado correctamente.');
        $this->command?->line('Escenarios creados:');
        $this->command?->line('- Lead listo para onboarding manual: lead.ready@noretel.test');
        $this->command?->line('- Lead con factibilidad y reserva activas: lead.factibilidad@noretel.test');
        $this->command?->line('- Lead convertido con cliente, direcciones y contactos: lead.convertido@noretel.test');
        $this->command?->line('- Lead duplicado para validar deteccion: lead.duplicado@noretel.test');
    }

    protected function upsertZone(array $attributes): Zone
    {
        return Zone::updateOrCreate(
            ['code' => $attributes['code']],
            $attributes,
        );
    }

    protected function upsertUser(array $attributes): User
    {
        return User::updateOrCreate(
            ['email' => $attributes['email']],
            [
                'name' => $attributes['name'],
                'password' => Hash::make('password'),
                'phone' => $attributes['phone'],
                'is_active' => true,
                'zone_id' => $attributes['zone_id'],
            ],
        );
    }

    protected function upsertNode(array $attributes): Node
    {
        return Node::updateOrCreate(
            ['code' => $attributes['code']],
            $attributes,
        );
    }

    protected function upsertNapBox(array $attributes): NapBox
    {
        return NapBox::updateOrCreate(
            ['code' => $attributes['code']],
            $attributes,
        );
    }

    protected function upsertLead(array $lookup, array $attributes): Lead
    {
        return Lead::updateOrCreate($lookup, $attributes);
    }

    protected function syncNapPorts(NapBox $napBox, array $ports): void
    {
        foreach ($ports as $portNumber => $data) {
            NapPort::updateOrCreate(
                [
                    'nap_box_id' => $napBox->id,
                    'port_number' => $portNumber,
                ],
                [
                    'status' => $data['status'],
                    'subscription_id' => null,
                    'label' => $data['label'],
                    'notes' => $data['notes'],
                ],
            );
        }
    }
}
