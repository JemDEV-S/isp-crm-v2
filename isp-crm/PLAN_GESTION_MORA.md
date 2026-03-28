# Plan de Implementación: Gestión de Mora (Dunning)

## Estado Actual del Proyecto

### Ya Implementado

| Componente | Ubicación | Estado |
|---|---|---|
| `SubscriptionStatus::SUSPENDED` | `Modules/Subscription/app/Enums/SubscriptionStatus.php` | Completo |
| `SubscriptionService::suspend()` | `Modules/Subscription/app/Services/SubscriptionService.php` | Funcional |
| `NetworkProvisioningService::suspendService()` | `Modules/Network/app/Services/NetworkProvisioningService.php` | Agrega IP a lista MOROSOS en RouterOS |
| `SuspendNetworkService` listener | `Modules/Subscription/app/Listeners/SuspendNetworkService.php` | Escucha `SubscriptionSuspended` |
| `SubscriptionSuspended` evento | `Modules/Subscription/app/Events/SubscriptionSuspended.php` | Completo |
| `Invoice` con `status`, `due_date`, `paid_at` | `Modules/Finance/app/Entities/Invoice.php` | Básico |
| `Wallet` modelo | `Modules/Finance/app/Entities/Wallet.php` | Completo |
| `SubscriptionStatusHistory` | `Modules/Subscription/app/Entities/SubscriptionStatusHistory.php` | Registra cambios |

### No Implementado

1. Motor de dunning parametrizable (`DunningPolicy`, `DunningStage`)
2. Registro de ejecuciones de cobranza (`DunningExecution`)
3. Promesas de pago (`PromiseToPay`)
4. Disputas de factura (`InvoiceDispute`)
5. Casos de cobranza (`CollectionCase`)
6. Clasificación de aging por antigüedad
7. Job automático de procesamiento de mora
8. Exclusiones formales (disputa, promesa, acuerdo)
9. Eventos de dominio del ciclo de dunning

---

## Fase 1: Migraciones

### 1.1 Tabla `dunning_policies`

**Archivo**: `Modules/Finance/database/migrations/2026_03_28_140000_create_dunning_policies_table.php`

```
dunning_policies
├── id
├── name varchar(100)                  — "Política estándar residencial"
├── code varchar(30) unique            — "standard_residential"
├── description text nullable
├── is_default boolean default false
├── is_active boolean default true
├── applies_to varchar(30) nullable    — segmento, zona, producto (null = todos)
├── applies_to_value varchar(50) nullable
├── timestamps
```

### 1.2 Tabla `dunning_stages`

**Archivo**: `Modules/Finance/database/migrations/2026_03_28_140100_create_dunning_stages_table.php`

```
dunning_stages
├── id
├── dunning_policy_id FK → dunning_policies
├── stage_order smallint               — 1, 2, 3...
├── name varchar(50)                   — "Primer recordatorio"
├── code varchar(30)                   — "reminder_1", "suspension_warning", "service_cut"
├── action_type varchar(30)            — reminder, warning, suspension, pre_termination, write_off
├── min_days_overdue smallint          — 0
├── max_days_overdue smallint          — 3
├── channels json                      — ["email", "sms", "whatsapp"]
├── template_code varchar(50) nullable — código de plantilla de notificación
├── auto_execute boolean default true
├── requires_approval boolean default false
├── metadata json nullable             — configuración extra
├── timestamps

INDEX: dunning_policy_id + stage_order (unique)
INDEX: action_type
```

### 1.3 Tabla `dunning_executions`

**Archivo**: `Modules/Finance/database/migrations/2026_03_28_140200_create_dunning_executions_table.php`

```
dunning_executions
├── id
├── invoice_id FK → invoices
├── subscription_id FK → subscriptions
├── customer_id FK → customers
├── dunning_stage_id FK → dunning_stages
├── action_type varchar(30)
├── channel varchar(30) nullable       — email, sms, whatsapp, system
├── status varchar(20)                 — executed, skipped, failed
├── result text nullable               — resultado de la acción
├── skip_reason varchar(50) nullable   — promise_active, dispute_open, manual_block
├── days_overdue smallint
├── amount_overdue decimal(10,2)
├── executed_by varchar(20)            — job, manual, system
├── job_run_id varchar(50) nullable    — ID de la corrida del job
├── metadata json nullable
├── executed_at timestamp
├── timestamps

INDEX: invoice_id + dunning_stage_id (unique, idempotencia)
INDEX: subscription_id
INDEX: executed_at
INDEX: status
```

### 1.4 Tabla `promises_to_pay`

**Archivo**: `Modules/Finance/database/migrations/2026_03_28_140300_create_promises_to_pay_table.php`

```
promises_to_pay
├── id
├── subscription_id FK → subscriptions
├── customer_id FK → customers
├── invoice_id FK nullable → invoices   — puede cubrir una o varias
├── promised_amount decimal(10,2)
├── promise_date date                   — fecha comprometida de pago
├── status varchar(20)                  — pending, fulfilled, broken, cancelled
├── source_channel varchar(30)          — phone, office, web, whatsapp
├── notes text nullable
├── approved_by FK nullable → users
├── approved_at timestamp nullable
├── fulfilled_at timestamp nullable
├── broken_at timestamp nullable
├── max_extensions smallint default 0
├── extensions_used smallint default 0
├── created_by FK nullable → users
├── timestamps

INDEX: subscription_id + status
INDEX: promise_date
INDEX: status
```

### 1.5 Tabla `invoice_disputes`

**Archivo**: `Modules/Finance/database/migrations/2026_03_28_140400_create_invoice_disputes_table.php`

```
invoice_disputes
├── id
├── invoice_id FK → invoices
├── customer_id FK → customers
├── reason_code varchar(30)            — incorrect_amount, duplicate, service_issue, billing_error
├── description text
├── status varchar(20)                 — open, under_review, resolved_favor_customer, resolved_favor_company, closed
├── resolution text nullable
├── resolved_by FK nullable → users
├── resolved_at timestamp nullable
├── created_by FK nullable → users
├── timestamps

INDEX: invoice_id
INDEX: status
```

### 1.6 Tabla `collection_cases`

**Archivo**: `Modules/Finance/database/migrations/2026_03_28_140500_create_collection_cases_table.php`

```
collection_cases
├── id
├── customer_id FK → customers
├── subscription_id FK nullable → subscriptions
├── total_debt decimal(10,2)
├── status varchar(20)                 — open, in_progress, recovered, written_off, sent_external
├── priority varchar(10)               — low, medium, high, critical
├── assigned_to FK nullable → users
├── external_agency varchar(100) nullable
├── sent_to_external_at timestamp nullable
├── closed_at timestamp nullable
├── close_reason varchar(30) nullable
├── notes text nullable
├── metadata json nullable
├── timestamps

INDEX: customer_id
INDEX: status
INDEX: priority
```

### 1.7 Campo `aging_bucket` en `invoices`

**Archivo**: `Modules/Finance/database/migrations/2026_03_28_140600_add_aging_fields_to_invoices_table.php`

Campos nuevos en `invoices`:
- `days_overdue` smallint default 0
- `aging_bucket` varchar(10) nullable — `current`, `1-15`, `16-30`, `31-60`, `61-90`, `90+`
- `last_dunning_stage_id` FK nullable → `dunning_stages`
- `dunning_paused` boolean default false
- `dunning_pause_reason` varchar(50) nullable

---

## Fase 2: Enums

**Archivo**: `Modules/Finance/app/Enums/DunningActionType.php`
```php
enum DunningActionType: string
{
    case REMINDER = 'reminder';
    case WARNING = 'warning';
    case SUSPENSION = 'suspension';
    case PRE_TERMINATION = 'pre_termination';
    case WRITE_OFF = 'write_off';
    case EXTERNAL_COLLECTION = 'external_collection';
}
```

**Archivo**: `Modules/Finance/app/Enums/PromiseStatus.php`
```php
enum PromiseStatus: string
{
    case PENDING = 'pending';
    case FULFILLED = 'fulfilled';
    case BROKEN = 'broken';
    case CANCELLED = 'cancelled';
}
```

**Archivo**: `Modules/Finance/app/Enums/DisputeStatus.php`
```php
enum DisputeStatus: string
{
    case OPEN = 'open';
    case UNDER_REVIEW = 'under_review';
    case RESOLVED_FAVOR_CUSTOMER = 'resolved_favor_customer';
    case RESOLVED_FAVOR_COMPANY = 'resolved_favor_company';
    case CLOSED = 'closed';
}
```

**Archivo**: `Modules/Finance/app/Enums/DisputeReasonCode.php`
```php
enum DisputeReasonCode: string
{
    case INCORRECT_AMOUNT = 'incorrect_amount';
    case DUPLICATE_CHARGE = 'duplicate';
    case SERVICE_ISSUE = 'service_issue';
    case BILLING_ERROR = 'billing_error';
    case UNAUTHORIZED_CHARGE = 'unauthorized';
    case OTHER = 'other';
}
```

**Archivo**: `Modules/Finance/app/Enums/CollectionCaseStatus.php`
```php
enum CollectionCaseStatus: string
{
    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case RECOVERED = 'recovered';
    case WRITTEN_OFF = 'written_off';
    case SENT_EXTERNAL = 'sent_external';
}
```

**Archivo**: `Modules/Finance/app/Enums/AgingBucket.php`
```php
enum AgingBucket: string
{
    case CURRENT = 'current';
    case D1_15 = '1-15';
    case D16_30 = '16-30';
    case D31_60 = '31-60';
    case D61_90 = '61-90';
    case D90_PLUS = '90+';

    public static function fromDays(int $days): self
    {
        return match (true) {
            $days <= 0 => self::CURRENT,
            $days <= 15 => self::D1_15,
            $days <= 30 => self::D16_30,
            $days <= 60 => self::D31_60,
            $days <= 90 => self::D61_90,
            default => self::D90_PLUS,
        };
    }
}
```

---

## Fase 3: Entidades

### 3.1 Modelos nuevos

- `Modules/Finance/app/Entities/DunningPolicy.php` — relación `hasMany(DunningStage)`
- `Modules/Finance/app/Entities/DunningStage.php` — relación `belongsTo(DunningPolicy)`, `hasMany(DunningExecution)`
- `Modules/Finance/app/Entities/DunningExecution.php` — relaciones con Invoice, Subscription, Customer, DunningStage
- `Modules/Finance/app/Entities/PromiseToPay.php` — relaciones con Subscription, Customer, Invoice
- `Modules/Finance/app/Entities/InvoiceDispute.php` — relaciones con Invoice, Customer
- `Modules/Finance/app/Entities/CollectionCase.php` — relaciones con Customer, Subscription

### 3.2 Actualizar modelo `Invoice`

Agregar a `$fillable`: `days_overdue`, `aging_bucket`, `last_dunning_stage_id`, `dunning_paused`, `dunning_pause_reason`

Agregar relaciones:
```php
public function dunningExecutions(): HasMany
public function disputes(): HasMany
public function lastDunningStage(): BelongsTo
```

Agregar scopes:
```php
public function scopeOverdue($query)  // due_date < now() AND status NOT IN (paid, cancelled)
public function scopeAgingBucket($query, AgingBucket $bucket)
public function scopeDunningEligible($query)  // overdue + not paused + not disputed
```

### 3.3 Agregar relaciones a `Subscription`

```php
public function promisesToPay(): HasMany
public function collectionCases(): HasMany
public function hasActivePromise(): bool  // promise pending + promise_date >= today
```

---

## Fase 4: Servicios

### 4.1 DunningService

**Archivo**: `Modules/Finance/app/Services/DunningService.php`

```php
class DunningService
{
    public function __construct(
        protected SubscriptionService $subscriptionService,
        protected InvoiceService $invoiceService,
    ) {}

    /**
     * Punto de entrada del job. Procesa todas las facturas elegibles.
     */
    public function processAll(string $jobRunId): DunningRunResult;

    /**
     * Procesa dunning para una factura individual.
     */
    public function processInvoice(Invoice $invoice, string $jobRunId): ?DunningExecution;

    /**
     * Determina si una factura es elegible para dunning.
     */
    public function isEligible(Invoice $invoice): bool;

    /**
     * Obtiene las exclusiones activas para una factura.
     */
    public function getExclusions(Invoice $invoice): array;

    /**
     * Determina la etapa de dunning correspondiente.
     */
    public function resolveStage(Invoice $invoice, int $daysOverdue): ?DunningStage;

    /**
     * Obtiene la política aplicable a la suscripción.
     */
    public function resolvePolicy(Subscription $subscription): DunningPolicy;

    /**
     * Ejecuta la acción de dunning (notificación, suspensión, etc.).
     */
    protected function executeAction(Invoice $invoice, DunningStage $stage, string $jobRunId): DunningExecution;

    /**
     * Ejecuta suspensión por mora con validaciones.
     */
    protected function executeSuspension(Invoice $invoice): void;
}
```

**Lógica de `processInvoice`**:
```
1. Calcular days_overdue = now() - due_date
2. Actualizar aging_bucket en la factura
3. Resolver política de dunning para la suscripción
4. Resolver etapa según days_overdue
5. Si no hay etapa → retornar null (fuera de rango)
6. Verificar idempotencia: ya ejecutada para invoice_id + stage_id?
7. Verificar exclusiones:
   - ¿Tiene promesa de pago vigente? → skip (promise_active)
   - ¿Tiene disputa abierta? → skip (dispute_open)
   - ¿Dunning pausado manualmente? → skip (manual_block)
8. Ejecutar acción según action_type:
   - reminder → enviar notificación por canales configurados
   - warning → enviar aviso de corte inminente
   - suspension → executeSuspension()
   - pre_termination → enviar aviso final
   - write_off/external → crear CollectionCase
9. Registrar DunningExecution
10. Disparar evento
```

**Lógica de `executeSuspension`**:
```
1. Verificar que suscripción canBeSuspended()
2. Verificar que NO hay promesa activa
3. Verificar que NO hay disputa abierta
4. Llamar SubscriptionService::suspend() con razón "Suspensión por mora"
5. El listener SuspendNetworkService se encarga del corte técnico
6. Si falla el corte técnico → registrar incidencia, NO asumir corte exitoso
```

### 4.2 AgingService

**Archivo**: `Modules/Finance/app/Services/AgingService.php`

```php
class AgingService
{
    /**
     * Actualiza aging de todas las facturas vencidas.
     */
    public function refreshAll(): int;

    /**
     * Actualiza aging de una factura.
     */
    public function refresh(Invoice $invoice): void;

    /**
     * Genera reporte de aging por buckets.
     */
    public function getAgingReport(array $filters = []): array;

    /**
     * Obtiene el resumen de cartera vencida por zona/segmento.
     */
    public function getAgingSummary(): array;
}
```

### 4.3 PromiseToPayService

**Archivo**: `Modules/Finance/app/Services/PromiseToPayService.php`

```php
class PromiseToPayService
{
    public function create(array $data): PromiseToPay;
    public function approve(PromiseToPay $promise, int $approvedBy): PromiseToPay;
    public function fulfill(PromiseToPay $promise): PromiseToPay;
    public function breakPromise(PromiseToPay $promise): PromiseToPay;
    public function cancel(PromiseToPay $promise, string $reason): PromiseToPay;
    public function extend(PromiseToPay $promise, Carbon $newDate): PromiseToPay;
    public function processExpired(): int;  // Usado por job diario
    public function hasActivePromise(int $subscriptionId): bool;
}
```

**Reglas de `create`**:
- `promise_date` máximo 7 días desde hoy (configurable)
- Solo 1 promesa activa por suscripción
- `max_extensions` configurable, default 1

**Reglas de `processExpired`** (job diario):
- Buscar promesas donde `promise_date < today` y `status = pending`
- Marcar como `broken` → disparar `PromiseToPayBroken`
- Reanudar dunning automáticamente

### 4.4 InvoiceDisputeService

**Archivo**: `Modules/Finance/app/Services/InvoiceDisputeService.php`

```php
class InvoiceDisputeService
{
    public function open(array $data): InvoiceDispute;
    public function resolve(InvoiceDispute $dispute, string $resolution, string $status): InvoiceDispute;
    public function close(InvoiceDispute $dispute): InvoiceDispute;
    public function hasOpenDispute(int $invoiceId): bool;
}
```

### 4.5 CollectionCaseService

**Archivo**: `Modules/Finance/app/Services/CollectionCaseService.php`

```php
class CollectionCaseService
{
    public function open(int $customerId, ?int $subscriptionId, float $totalDebt): CollectionCase;
    public function assign(CollectionCase $case, int $userId): CollectionCase;
    public function sendToExternal(CollectionCase $case, string $agency): CollectionCase;
    public function markRecovered(CollectionCase $case): CollectionCase;
    public function writeOff(CollectionCase $case, string $reason): CollectionCase;
}
```

---

## Fase 5: Jobs y Commands

### 5.1 Job de Dunning

**Archivo**: `Modules/Finance/app/Jobs/ProcessDunningJob.php`

```php
class ProcessDunningJob implements ShouldQueue
{
    public int $tries = 1;
    public int $timeout = 600;

    public function handle(DunningService $dunningService): void
    {
        $jobRunId = (string) Str::uuid();
        $dunningService->processAll($jobRunId);
    }
}
```

### 5.2 Job de Promesas Expiradas

**Archivo**: `Modules/Finance/app/Jobs/ProcessExpiredPromisesJob.php`

```php
class ProcessExpiredPromisesJob implements ShouldQueue
{
    public function handle(PromiseToPayService $promiseService): void
    {
        $promiseService->processExpired();
    }
}
```

### 5.3 Job de Aging

**Archivo**: `Modules/Finance/app/Jobs/RefreshAgingJob.php`

```php
class RefreshAgingJob implements ShouldQueue
{
    public function handle(AgingService $agingService): void
    {
        $agingService->refreshAll();
    }
}
```

### 5.4 Artisan Command

**Archivo**: `Modules/Finance/app/Console/ProcessDunningCommand.php`

```php
protected $signature = 'finance:process-dunning
    {--sync : Ejecutar de forma síncrona}
    {--invoice= : Procesar una sola factura por ID}
    {--dry-run : Simular sin ejecutar acciones}';
```

### 5.5 Scheduler

```php
// En FinanceServiceProvider::boot()
$schedule->command('finance:process-dunning --sync')->dailyAt('08:00')->withoutOverlapping();
$schedule->job(new ProcessExpiredPromisesJob)->dailyAt('07:00');
$schedule->job(new RefreshAgingJob)->dailyAt('06:00');
```

---

## Fase 6: Eventos y Listeners

### 6.1 Eventos

| Evento | Datos |
|---|---|
| `DunningStageTriggered` | `DunningExecution`, `Invoice` |
| `CollectionReminderSent` | `DunningExecution`, canal, resultado |
| `SuspensionWarningIssued` | `Invoice`, `Subscription` |
| `SubscriptionSuspensionRequested` | `Subscription`, `Invoice`, razón |
| `SubscriptionSuspendedForDebt` | `Subscription`, `Invoice` |
| `PromiseToPayCreated` | `PromiseToPay` |
| `PromiseToPayApproved` | `PromiseToPay` |
| `PromiseToPayFulfilled` | `PromiseToPay` |
| `PromiseToPayBroken` | `PromiseToPay` |
| `InvoiceDisputeOpened` | `InvoiceDispute` |
| `InvoiceDisputeResolved` | `InvoiceDispute` |
| `CollectionCaseOpened` | `CollectionCase` |

### 6.2 Listeners

| Listener | Escucha | Acción |
|---|---|---|
| `PauseDunningOnPromise` | `PromiseToPayCreated` | Pausar dunning de la factura |
| `ResumeDunningOnBrokenPromise` | `PromiseToPayBroken` | Reanudar dunning |
| `PauseDunningOnDispute` | `InvoiceDisputeOpened` | Pausar dunning |
| `ResumeDunningOnDisputeResolved` | `InvoiceDisputeResolved` | Reanudar si no resolvió a favor del cliente |
| `NotifyCustomerOnDunning` | `DunningStageTriggered` | Enviar notificación según canales del stage |
| `CreateCollectionCaseOnWriteOff` | `DunningStageTriggered` (si action = write_off) | Crear CollectionCase |

---

## Fase 7: Configuración

**Archivo**: `Modules/Finance/config/config.php` (agregar sección)

```php
'dunning' => [
    'enabled' => env('DUNNING_ENABLED', true),
    'default_policy' => 'standard_residential',
    'promise_max_days' => env('DUNNING_PROMISE_MAX_DAYS', 7),
    'promise_max_extensions' => env('DUNNING_PROMISE_MAX_EXTENSIONS', 1),
    'suspension_requires_approval' => env('DUNNING_SUSPENSION_APPROVAL', false),
    'auto_resume_after_broken_promise' => true,
    'exclude_corporate_from_auto_suspension' => false,
],
```

---

## Fase 8: Seeder de Política Estándar

**Archivo**: `Modules/Finance/database/seeders/DunningPolicySeeder.php`

```
Política: "Estándar Residencial" (standard_residential)
├── Stage 1: Recordatorio email/SMS         (día 0-2,   action: reminder)
├── Stage 2: Segundo recordatorio + WhatsApp (día 3-6,   action: reminder)
├── Stage 3: Aviso de corte inminente       (día 7-9,   action: warning)
├── Stage 4: Corte automático               (día 10-29, action: suspension)
├── Stage 5: Aviso final pre-cancelación    (día 30-59, action: pre_termination)
└── Stage 6: Envío a cobranza externa       (día 60+,   action: external_collection)
```

---

## Fase 9: API Endpoints

| Método | Ruta | Acción |
|---|---|---|
| `GET` | `/api/finance/dunning/policies` | Listar políticas |
| `POST` | `/api/finance/dunning/policies` | Crear política |
| `GET` | `/api/finance/dunning/executions` | Historial de ejecuciones |
| `GET` | `/api/finance/aging/report` | Reporte de aging |
| `GET` | `/api/finance/aging/summary` | Resumen por bucket |
| `POST` | `/api/finance/promises-to-pay` | Crear promesa de pago |
| `POST` | `/api/finance/promises-to-pay/{id}/approve` | Aprobar promesa |
| `POST` | `/api/finance/promises-to-pay/{id}/cancel` | Cancelar promesa |
| `POST` | `/api/finance/promises-to-pay/{id}/extend` | Extender plazo |
| `POST` | `/api/finance/invoices/{id}/dispute` | Abrir disputa |
| `POST` | `/api/finance/disputes/{id}/resolve` | Resolver disputa |
| `GET` | `/api/finance/collection-cases` | Listar casos |
| `POST` | `/api/finance/collection-cases/{id}/assign` | Asignar caso |

**Controllers**:
- `Modules/Finance/app/Http/Controllers/DunningController.php`
- `Modules/Finance/app/Http/Controllers/PromiseToPayController.php`
- `Modules/Finance/app/Http/Controllers/DisputeController.php`
- `Modules/Finance/app/Http/Controllers/CollectionCaseController.php`
- `Modules/Finance/app/Http/Controllers/AgingController.php`

---

## Fase 10: Tests

### Tests Unitarios

**`DunningServiceTest`**:
- Factura vencida 5 días → ejecuta stage "recordatorio"
- Factura vencida 10 días → ejecuta stage "suspensión"
- Factura con promesa activa → skip con razón `promise_active`
- Factura con disputa abierta → skip con razón `dispute_open`
- Factura con dunning pausado → skip con razón `manual_block`
- Idempotencia: misma stage no se ejecuta dos veces
- Política por segmento se resuelve correctamente

**`PromiseToPayServiceTest`**:
- Crear promesa válida
- No permite 2 promesas activas en misma suscripción
- `processExpired` marca como broken si pasó `promise_date`
- Extensión respeta `max_extensions`

**`AgingServiceTest`**:
- Clasifica correctamente en buckets
- Reporte agrupa por bucket con totales

### Tests de Integración

**`DunningIntegrationTest`**:
- Flujo completo: factura vence → recordatorio → suspensión → pago → reactivación
- Flujo con promesa: vence → promesa → no corta → promesa incumplida → corta
- Flujo con disputa: vence → disputa → no corta → resuelta → retoma dunning

---

## Resumen de Archivos

### Nuevos (~35)

| Tipo | Cantidad |
|---|---|
| Migraciones | 7 |
| Enums | 6 |
| Entidades | 6 |
| Servicios | 5 |
| Jobs | 3 |
| Command | 1 |
| Eventos | 12 |
| Listeners | 6 |
| Controllers | 5 |
| Seeder | 1 |

### A Modificar (~3)

- `Invoice.php` — agregar fillable, relaciones, scopes
- `Subscription.php` — agregar relaciones `promisesToPay()`, `collectionCases()`
- `FinanceServiceProvider.php` — registrar commands y schedule
- `config.php` (Finance) — agregar sección dunning

---

## Criterios de Aceptación

1. El job se ejecuta diario a las 08:00 y procesa facturas vencidas
2. Cada etapa se ejecuta una sola vez por factura (idempotente)
3. Promesas de pago vigentes congelan acciones de corte
4. Disputas abiertas excluyen la factura del motor automático
5. La suspensión solo procede si `canBeSuspended()` y sin exclusiones
6. Promesas incumplidas se detectan automáticamente y reanudan dunning
7. El reporte de aging muestra clasificación por buckets
8. Casos de cobranza se crean al superar umbral de días configurado
9. Todo queda trazado en `dunning_executions` con usuario, canal y resultado
10. La configuración es parametrizable por política y etapa
