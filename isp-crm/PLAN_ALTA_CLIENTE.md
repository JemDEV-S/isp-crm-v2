# Plan de Implementación: Proceso de Alta de Cliente

## Estado Actual del Proyecto

### ✅ Ya Implementado
- **CRM**: Lead, Customer, Address, Contact, LeadService con conversión básica
- **FieldOps**: WorkOrder, Appointment, ChecklistResponse, MaterialUsage, TechnicianLocation
- **Subscription**: Subscription, ServiceInstance, eventos básicos (SubscriptionCreated, SubscriptionActivated)
- **Workflow**: Sistema completo con Token, Transition, WorkflowDefinition, HasWorkflow trait
- **Network**: Device, NapBox, NapPort, IpPool, DTOs (FeasibilityResultDTO, ProvisionServiceDTO)
- **Inventory**: Product, Serial, Stock, Warehouse, Movement, MovementRequest
- **Catalog**: Plan, Promotion, Addon
- **AccessControl**: User, Role, Zone

### ❌ Falta Implementar

1. Sistema de detección de duplicados
2. Servicio de factibilidad con reserva temporal
3. Validación documental y aceptación contractual
4. Snapshot comercial en suscripciones
5. Sistema de aprovisionamiento de red
6. Geofencing para validación de ubicación
7. Validación de supervisor estructurada
8. Wallet e Invoice en Finance
9. Causales estructuradas para excepciones
10. Eventos de dominio faltantes
11. Orquestación completa del flujo

---

## Fase 1: Preparación del Módulo CRM
**Objetivo**: Mejorar gestión de leads con detección de duplicados y factibilidad

### 1.1 Detección de Duplicados

**Archivo**: `Modules/Crm/app/Services/DuplicateDetectionService.php`
```php
<?php
namespace Modules\Crm\Services;

class DuplicateDetectionService
{
    public function detectDuplicateLeads(array $data): array;
    public function detectDuplicateCustomers(array $data): array;
    public function markAsDuplicate(Lead $lead, ?int $duplicateOfId): void;
    public function resolveDuplicate(Lead $lead, string $resolution): void;
}
```

**Migraciones**:
- `leads`: agregar columnas `is_duplicate`, `duplicate_of_id`, `duplicate_resolution`
- Índices: `document_type + document_number`, `phone`, `email`

**Eventos**:
- `LeadDuplicateDetected`: disparar cuando se detecte coincidencia

### 1.2 Sistema de Factibilidad

**Archivo**: `Modules/Crm/app/Services/FeasibilityService.php`
```php
<?php
namespace Modules\Crm\Services;

class FeasibilityService
{
    public function check(int $leadId, array $addressData): FeasibilityResult;
    public function reserveCapacity(int $napPortId, int $leadId, int $hours = 24): Reservation;
    public function releaseReservation(int $reservationId): void;
    public function extendReservation(int $reservationId, int $additionalHours): void;
}
```

**Nuevas Tablas**:
```sql
feasibility_requests
- id, lead_id, address_id, status, result_data, requested_at, resolved_at

capacity_reservations
- id, reservable_type, reservable_id (NAP port), lead_id, expires_at, released_at
```

**Eventos**:
- `FeasibilityConfirmed`
- `FeasibilityRejected`
- `CapacityReserved`

### 1.3 Normalización de Dirección

**Archivo**: `Modules/Crm/app/Services/AddressService.php`
```php
<?php
namespace Modules\Crm\Services;

class AddressService
{
    public function normalize(array $addressData): array;
    public function geocode(Address $address): void;
    public function validateGeoreference(float $lat, float $lng): bool;
}
```

**Migración**: `addresses` agregar columnas
- `georeference_quality`: enum(high, medium, low)
- `address_reference`: text
- `photo_url`: string

---

## Fase 2: Módulo de Contratación (Nuevo)

### 2.1 Crear Módulo Contracts
```bash
php artisan module:make Contracts
```

### 2.2 Entidades del Módulo

**Archivo**: `Modules/Contracts/app/Entities/Contract.php`
```php
<?php
namespace Modules\Contracts\Entities;

class Contract extends Model
{
    // Campos: lead_id, customer_id, subscription_id, plan_snapshot,
    // promotion_snapshot, installation_cost, terms_accepted_at,
    // acceptance_method, ip_address, user_agent
}
```

**Archivo**: `Modules/Contracts/app/Entities/ContractDocument.php`
```php
<?php
namespace Modules\Contracts\Entities;

class ContractDocument extends Model
{
    // Campos: contract_id, customer_id, document_type, document_number,
    // file_path, validated_at, validated_by
}
```

### 2.3 Servicio de Contratación

**Archivo**: `Modules/Contracts/app/Services/ContractService.php`
```php
<?php
namespace Modules\Contracts\Services;

class ContractService
{
    public function createContract(CreateContractDTO $dto): Contract;
    public function validateDocuments(int $contractId): bool;
    public function recordAcceptance(int $contractId, string $method): void;
    public function freezeCommercialSnapshot(int $contractId): array;
}
```

**DTOs**:
- `CreateContractDTO`: leadId, customerId, planId, addons, promotionId, billingDay
- `ContractSnapshotDTO`: datos congelados de precio y condiciones

**Eventos**:
- `ContractCreated`
- `ContractDocumentsValidated`
- `ContractTermsAccepted`

---

## Fase 3: Mejoras al Módulo Subscription

### 3.1 Snapshot Comercial

**Migración**: `subscriptions` agregar columna `commercial_snapshot` (json)

**Estructura del snapshot**:
```json
{
  "plan": {"id": 1, "name": "Plan 100MB", "price": 50.00, "parameters": {...}},
  "addons": [{"id": 2, "name": "IP Estática", "price": 10.00}],
  "promotion": {"id": 3, "name": "50% primer mes", "discount": 0.5},
  "installation_cost": 100.00,
  "first_invoice": 75.00,
  "billing_day": 15,
  "frozen_at": "2026-03-28T10:00:00Z"
}
```

### 3.2 Servicio de Activación

**Archivo**: `Modules/Subscription/app/Services/ActivationService.php`
```php
<?php
namespace Modules\Subscription\Services;

class ActivationService
{
    public function activate(int $subscriptionId, array $provisionResult): bool;
    public function canActivate(int $subscriptionId): bool;
    public function rollbackActivation(int $subscriptionId, string $reason): void;
}
```

### 3.3 Eventos Adicionales

- `SubscriptionReadyForActivation`
- `SubscriptionActivationFailed`
- `ServiceInstanceProvisioned`

---

## Fase 4: Sistema de Aprovisionamiento en Network

### 4.1 Servicio de Aprovisionamiento

**Archivo**: `Modules/Network/app/Services/ProvisioningService.php`
```php
<?php
namespace Modules\Network\Services;

class ProvisioningService
{
    public function provisionService(ProvisionServiceDTO $dto): ProvisionResult;
    public function deprovisionService(int $serviceInstanceId): bool;
    public function assignIpAddress(int $poolId): IpAddress;
    public function createPPPoECredentials(int $customerId): array;
    public function authorizeONU(string $mac, string $serial): bool;
}
```

**DTO**: `ProvisionServiceDTO`
- serviceInstanceId, customerId, napPortId, deviceSerial, deviceMac, planId

**DTO**: `ProvisionResult`
- success, ipAddress, pppoeUsername, pppoePassword, onuStatus, errors, provisionedAt

### 4.2 Actualizar ServiceInstance

**Migración**: `service_instances` agregar columnas
- `provision_data`: json (IP, credenciales, ONU status)
- `provision_status`: enum (pending, provisioning, active, failed, suspended)
- `provisioned_at`: timestamp

---

## Fase 5: Mejoras al Módulo FieldOps

### 5.1 Sistema de Geofencing

**Archivo**: `Modules/FieldOps/app/Services/GeofenceService.php`
```php
<?php
namespace Modules\FieldOps\Services;

class GeofenceService
{
    public function validateLocation(float $lat, float $lng, int $addressId, int $radiusMeters = 100): bool;
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float;
    public function recordViolation(int $workOrderId, float $distance): void;
}
```

### 5.2 Validación de Supervisor

**Archivo**: `Modules/FieldOps/app/Services/ValidationService.php`
```php
<?php
namespace Modules\FieldOps\Services;

class ValidationService
{
    public function validate(int $workOrderId, array $criteria): ValidationResult;
    public function reject(int $workOrderId, array $observations): void;
    public function requestCorrection(int $workOrderId, array $issues): void;
}
```

**Nueva Tabla**: `work_order_validations`
```sql
- id, work_order_id, validator_id, status, criteria_checked, observations, validated_at
```

### 5.3 Causales Estructuradas

**Nueva Tabla**: `work_order_exceptions`
```sql
- id, work_order_id, exception_type, causal_code, description, resolved_at
```

**Enum**: `ExceptionType`
- customer_absent, no_materials, no_feasibility, failed_provision, failed_validation, external_issue

---

## Fase 6: Módulo Finance - Invoice y Wallet

### 6.1 Entidades

**Archivo**: `Modules/Finance/app/Entities/Wallet.php`
```php
<?php
namespace Modules\Finance\Entities;

class Wallet extends Model
{
    // Campos: customer_id, balance, credit_limit, status
}
```

**Archivo**: `Modules/Finance/app/Entities/Invoice.php`
```php
<?php
namespace Modules\Finance\Entities;

class Invoice extends Model
{
    // Campos: customer_id, subscription_id, invoice_number, type,
    // subtotal, tax, total, due_date, paid_at, status
}
```

**Archivo**: `Modules/Finance/app/Entities/InvoiceItem.php`
```php
<?php
namespace Modules\Finance\Entities;

class InvoiceItem extends Model
{
    // Campos: invoice_id, concept, description, quantity, unit_price, subtotal, tax
}
```

### 6.2 Servicio de Facturación

**Archivo**: `Modules/Finance/app/Services/InvoiceService.php`
```php
<?php
namespace Modules\Finance\Services;

class InvoiceService
{
    public function generateInitialInvoice(int $subscriptionId): Invoice;
    public function calculateProrate(int $subscriptionId, Carbon $activationDate): float;
    public function sendInvoice(int $invoiceId): void;
}
```

**Eventos**:
- `InitialInvoiceGenerated`
- `InvoicePaymentReceived`

---

## Fase 7: Orquestación del Flujo Completo

### 7.1 Definición de Workflow "installation"

**Archivo**: `Modules/Workflow/database/seeders/InstallationWorkflowSeeder.php`

**Estados (Places)**:
1. `pending_schedule`
2. `scheduled`
3. `assigned`
4. `materials_reserved`
5. `in_transit`
6. `on_site`
7. `in_progress`
8. `pending_validation`
9. `completed`
10. `cancelled`
11. `failed`

**Transiciones**:
- `schedule`: pending_schedule → scheduled
- `assign`: scheduled → assigned
- `reserve_materials`: assigned → materials_reserved
- `start_transit`: materials_reserved → in_transit
- `arrive`: in_transit → on_site
- `start_work`: on_site → in_progress
- `submit_validation`: in_progress → pending_validation
- `approve`: pending_validation → completed
- `reject`: pending_validation → in_progress
- `cancel`: any → cancelled
- `mark_failed`: any → failed

### 7.2 Side Effects

**Transitions con efectos automáticos**:

**`assign`**:
- Crear Appointment
- Notificar técnico
- Notificar cliente

**`reserve_materials`**:
- Crear MovementRequest
- Reservar seriales específicos

**`arrive`**:
- Validar geofence (throw exception si falla)
- Registrar TechnicianLocation

**`submit_validation`**:
- Validar checklist completo
- Validar fotos obligatorias
- Notificar supervisor

**`approve`**:
- Confirmar consumo inventario
- Activar Subscription
- Aprovisionar red
- Generar Invoice inicial

**SideEffect Classes**:
```php
Modules/FieldOps/app/SideEffects/CreateAppointmentAction.php
Modules/FieldOps/app/SideEffects/ReserveMaterialsAction.php
Modules/FieldOps/app/SideEffects/ValidateGeofenceAction.php
Modules/FieldOps/app/SideEffects/CompleteInstallationAction.php
```

### 7.3 Orquestador Principal

**Archivo**: `Modules/Crm/app/Services/CustomerOnboardingOrchestrator.php`
```php
<?php
namespace Modules\Crm\Services;

class CustomerOnboardingOrchestrator
{
    public function __construct(
        protected DuplicateDetectionService $duplicateDetection,
        protected FeasibilityService $feasibilityService,
        protected ContractService $contractService,
        protected SubscriptionService $subscriptionService,
        protected WorkflowService $workflowService,
    ) {}

    public function startOnboarding(int $leadId): OnboardingResult;
    public function convertLeadToCustomer(ConvertLeadDTO $dto): Customer;
    public function createSubscriptionAndWorkflow(CreateSubscriptionDTO $dto): array;
    public function handleWorkflowCompletion(int $workOrderId): void;
}
```

---

## Fase 8: Eventos de Dominio

### 8.1 Eventos Faltantes

**Módulo CRM**:
- `LeadDuplicateDetected`
- `FeasibilityConfirmed`
- `FeasibilityRejected`

**Módulo Contracts**:
- `ContractCreated`
- `ContractDocumentsValidated`
- `ContractTermsAccepted`

**Módulo FieldOps**:
- `InstallationWorkflowStarted`
- `InstallationScheduled`
- `WorkOrderAssigned`
- `InstallationMaterialsReserved`
- `TechnicianDispatched`
- `TechnicianArrived`
- `InstallationSubmittedForValidation`
- `InstallationValidated`
- `InstallationRejected`

**Módulo Network**:
- `ProvisioningCompleted`
- `ProvisioningFailed`
- `CapacityReserved`

**Módulo Finance**:
- `InitialInvoiceGenerated`

### 8.2 Listeners

**Event**: `InstallationValidated` (WorkOrder completada)
**Listeners**:
- `ConfirmMaterialConsumption` (Inventory)
- `ActivateSubscription` (Subscription)
- `ProvisionNetworkService` (Network)
- `GenerateInitialInvoice` (Finance)
- `SendWelcomeEmail` (Notification)

---

## Fase 9: Testing y Validación

### 9.1 Tests Unitarios

**Servicios a testear**:
- `DuplicateDetectionService`
- `FeasibilityService`
- `ContractService`
- `ProvisioningService`
- `GeofenceService`
- `ValidationService`
- `InvoiceService`
- `CustomerOnboardingOrchestrator`

### 9.2 Tests de Integración

**Flujos completos**:
1. Lead → Customer → Subscription → WorkOrder → Completed → Activated
2. Lead duplicado detectado y resuelto
3. Factibilidad rechazada
4. Instalación rechazada por supervisor
5. Aprovisionamiento fallido

### 9.3 Test Feature

**Archivo**: `tests/Feature/CustomerOnboardingTest.php`
```php
/** @test */
public function complete_customer_onboarding_workflow()
{
    // Crear lead
    // Verificar factibilidad
    // Convertir a cliente
    // Crear suscripción
    // Agendar instalación
    // Completar instalación
    // Validar activación
    // Verificar factura generada
}
```

---

## Fase 10: Interfaces y API

### 10.1 API Endpoints

**Lead Management**:
- `POST /api/crm/leads` - Crear lead
- `POST /api/crm/leads/{id}/check-duplicates` - Verificar duplicados
- `POST /api/crm/leads/{id}/feasibility` - Solicitar factibilidad
- `POST /api/crm/leads/{id}/convert` - Convertir a cliente

**Contract Management**:
- `POST /api/contracts` - Crear contrato
- `POST /api/contracts/{id}/documents` - Subir documentos
- `POST /api/contracts/{id}/accept` - Aceptar términos

**Work Order Management**:
- `GET /api/field-ops/work-orders` - Listar órdenes
- `POST /api/field-ops/work-orders/{id}/start-transit` - Iniciar ruta
- `POST /api/field-ops/work-orders/{id}/arrive` - Marcar llegada
- `POST /api/field-ops/work-orders/{id}/complete` - Completar instalación
- `POST /api/field-ops/work-orders/{id}/validate` - Validar trabajo

### 10.2 Controllers

**Archivo**: `Modules/Crm/app/Http/Controllers/OnboardingController.php`
**Archivo**: `Modules/Contracts/app/Http/Controllers/ContractController.php`
**Archivo**: `Modules/FieldOps/app/Http/Controllers/InstallationController.php`

---

## Cronograma de Implementación

### Sprint 1 (1 semana)
- [x] Análisis de requerimientos
- [ ] Fase 1: CRM - Duplicados y Factibilidad
- [ ] Fase 2: Módulo Contracts

### Sprint 2 (1 semana)
- [ ] Fase 3: Subscription Snapshot
- [ ] Fase 4: Aprovisionamiento Network
- [ ] Fase 5: FieldOps Geofencing y Validación

### Sprint 3 (1 semana)
- [ ] Fase 6: Finance - Invoice y Wallet
- [ ] Fase 7: Orquestación Workflow
- [ ] Fase 8: Eventos y Listeners

### Sprint 4 (1 semana)
- [ ] Fase 9: Testing completo
- [ ] Fase 10: API y Controllers
- [ ] Documentación

---

## Criterios de Aceptación

✅ **El proceso está completo cuando**:

1. Un lead puede ser creado, detectar duplicados y solicitar factibilidad
2. La factibilidad reserva capacidad temporal (NAP/puerto)
3. El lead se convierte a Customer con validación documental completa
4. Se crea una Subscription con snapshot comercial congelado
5. Se inicia un WorkOrder de instalación con workflow "installation"
6. El técnico puede registrar su ruta, llegada y trabajo con geofencing
7. El supervisor puede validar o rechazar con causales estructuradas
8. Al aprobar, se ejecuta en transacción: consumo inventario, activación, aprovisionamiento, facturación
9. Todos los eventos de dominio se disparan correctamente
10. La factura inicial incluye instalación + prorrata según política
11. Métricas y KPIs se actualizan automáticamente
12. El proceso maneja excepciones con causales estructuradas
13. Tests de integración cubren flujo completo y casos edge
14. API REST completa y documentada

---

## Notas Técnicas

### Transacciones Críticas

**Conversión Lead → Customer**:
```php
DB::transaction(function() {
    $customer = Customer::create(...);
    $address = Address::create(...);
    $contact = Contact::create(...);
    $wallet = Wallet::create(...);
    $lead->update(['status' => 'won', 'converted_at' => now()]);
    event(new LeadConverted($lead, $customer));
});
```

**Activación de Suscripción**:
```php
DB::transaction(function() {
    // Confirmar inventario
    // Activar subscription
    // Aprovisionar red
    // Generar factura
    // Si falla cualquier paso, rollback completo
});
```

### Idempotencia

- Aprovisionamiento debe verificar si ya existe antes de ejecutar
- Factura inicial debe verificar si ya fue generada
- Consumo de inventario debe marcar serial como usado

### Logs y Auditoría

- Cada transición de workflow registra: user_id, timestamp, metadata
- Cada excepción registra: tipo, causal, usuario responsable, resolución
- Aprovisionamiento guarda respuesta completa del sistema de red

---

## Dependencias Externas

**Paquetes recomendados**:
- `spatie/laravel-event-sourcing`: para eventos de dominio robustos (opcional)
- `doctrine/dbal`: para manipulación avanzada de migraciones
- `league/fractal` o `spatie/laravel-fractal`: transformers para API
- `guzzlehttp/guzzle`: cliente HTTP para integración con sistemas de red

**Servicios externos**:
- Sistema de aprovisionamiento de red (API o CLI)
- Servicio de geocodificación (Google Maps API, OpenStreetMap)
- Sistema de notificaciones (email, SMS)

---

## Diagrama de Flujo Simplificado

```
Lead Created
    ↓
Duplicate Check → [duplicate detected?] → Review Queue
    ↓ [no duplicate]
Feasibility Check → [feasible?] → Lost
    ↓ [yes]
Reserve Capacity
    ↓
Convert to Customer
    ↓
Create Contract
    ↓
Validate Documents
    ↓
Accept Terms
    ↓
Create Subscription (draft)
    ↓
Create WorkOrder → Start Workflow
    ↓
Schedule → Assign → Reserve Materials
    ↓
In Transit → On Site → In Progress
    ↓
Submit Validation
    ↓
[approved?] → [no] → back to In Progress
    ↓ [yes]
Complete WorkOrder
    ↓
[Transaction Begin]
    ├─ Confirm Inventory
    ├─ Activate Subscription
    ├─ Provision Network
    └─ Generate Invoice
[Transaction End]
    ↓
Customer Active ✅
```

---

## Resumen Ejecutivo

Este plan cubre la implementación completa del proceso de alta de cliente con:

- **8 módulos** integrados (CRM, Contracts, Subscription, Network, FieldOps, Inventory, Finance, Workflow)
- **~30 clases nuevas** (Services, DTOs, Events, Listeners)
- **~15 migraciones** nuevas
- **~50 tests** (unitarios + integración)
- **~20 endpoints** API
- **4 sprints** de 1 semana cada uno

El resultado final será un sistema robusto, auditable, con manejo de excepciones estructurado y completamente funcional para el onboarding de clientes en un ISP.
