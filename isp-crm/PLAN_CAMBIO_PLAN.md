# Plan de Implementación: Proceso de Cambio de Plan

## Estado Actual del Proyecto

### Ya Implementado

| Componente | Ubicación | Estado |
|---|---|---|
| `SubscriptionService::changePlan()` | `Modules/Subscription/app/Services/SubscriptionService.php` | Básico: solo actualiza `plan_id` y `monthly_price` |
| `SubscriptionController::changePlan()` | `Modules/Subscription/app/Http/Controllers/SubscriptionController.php` | Valida `plan_id` + `immediate` |
| API `POST /subscriptions/{id}/change-plan` | `Modules/Subscription/routes/api.php` | Funcional |
| `SubscriptionStatusHistory` con metadata | `Modules/Subscription/app/Entities/SubscriptionStatusHistory.php` | Registra `old_plan_id`, `new_plan_id`, `immediate` |
| `Plan` modelo con `price`, `installation_fee`, `ip_pool_id` | `Modules/Catalog/app/Entities/Plan.php` | Completo |
| `NetworkProvisioningService` | `Modules/Network/app/Services/NetworkProvisioningService.php` | Soporte para reprovisión |
| `SubscriptionContractService::freezeCommercialSnapshot()` | `Modules/Subscription/app/Services/SubscriptionContractService.php` | Genera snapshot comercial |
| `BillingCycle` enum | `Modules/Subscription/app/Enums/BillingCycle.php` | monthly, bimonthly, quarterly, annual |

### No Implementado

1. `PlanChangeRequest` — solicitud formal con fecha efectiva
2. Validación de elegibilidad (permanencia, bloqueos, factibilidad técnica)
3. Cálculo de prorrata por cambio de plan (crédito/débito)
4. Diferenciación upgrade vs downgrade
5. Reprovisión de red como parte del cambio
6. Rollback controlado si falla la reprovisión
7. Generación de nota de crédito o cargo por diferencia
8. Snapshot del plan anterior y nuevo
9. Control de concurrencia (solo 1 solicitud abierta)
10. Eventos de dominio del cambio de plan

---

## Fase 1: Migraciones

### 1.1 Tabla `plan_change_requests`

**Archivo**: `Modules/Subscription/database/migrations/2026_03_28_160000_create_plan_change_requests_table.php`

```
plan_change_requests
├── id
├── uuid unique
├── subscription_id FK → subscriptions
├── customer_id FK → customers
├── old_plan_id FK → plans
├── new_plan_id FK → plans
├── change_type varchar(20)              — upgrade, downgrade, lateral
├── effective_mode varchar(20)           — immediate, next_cycle, scheduled
├── effective_at timestamp nullable      — fecha efectiva real
├── scheduled_for date nullable          — si es programado
├── status varchar(20)                   — pending, approved, rejected, executing, completed, failed, cancelled
├── old_plan_snapshot json               — snapshot del plan original
├── new_plan_snapshot json               — snapshot del plan destino
├── old_monthly_price decimal(10,2)
├── new_monthly_price decimal(10,2)
├── prorate_credit decimal(10,2) default 0  — crédito por días no consumidos
├── prorate_debit decimal(10,2) default 0   — cargo por nuevos días
├── net_difference decimal(10,2) default 0  — debit - credit
├── billing_adjustment_type varchar(20)  — invoice, credit_note, wallet_credit, none
├── feasibility_checked boolean default false
├── feasibility_result json nullable
├── requires_approval boolean default false
├── approved_by FK nullable → users
├── approved_at timestamp nullable
├── rejection_reason text nullable
├── provision_status varchar(20) nullable — pending, success, failed, rolled_back
├── provision_result json nullable
├── notes text nullable
├── requested_by FK nullable → users
├── executed_at timestamp nullable
├── timestamps
├── softDeletes

INDEX: subscription_id + status
INDEX: status
INDEX: scheduled_for
INDEX: change_type
```

### 1.2 Agregar campo a `subscriptions`

**Archivo**: `Modules/Subscription/database/migrations/2026_03_28_160100_add_plan_change_tracking_to_subscriptions_table.php`

Campos nuevos en `subscriptions`:
- `has_pending_plan_change` boolean default false
- `last_plan_change_at` timestamp nullable
- `minimum_stay_until` date nullable — fecha mínima de permanencia

---

## Fase 2: Enums

**Archivo**: `Modules/Subscription/app/Enums/PlanChangeType.php`
```php
enum PlanChangeType: string
{
    case UPGRADE = 'upgrade';
    case DOWNGRADE = 'downgrade';
    case LATERAL = 'lateral';

    public static function determine(float $oldPrice, float $newPrice): self
    {
        if ($newPrice > $oldPrice) return self::UPGRADE;
        if ($newPrice < $oldPrice) return self::DOWNGRADE;
        return self::LATERAL;
    }
}
```

**Archivo**: `Modules/Subscription/app/Enums/EffectiveMode.php`
```php
enum EffectiveMode: string
{
    case IMMEDIATE = 'immediate';
    case NEXT_CYCLE = 'next_cycle';
    case SCHEDULED = 'scheduled';
}
```

**Archivo**: `Modules/Subscription/app/Enums/PlanChangeStatus.php`
```php
enum PlanChangeStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case EXECUTING = 'executing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
}
```

**Archivo**: `Modules/Subscription/app/Enums/BillingAdjustmentType.php`
```php
enum BillingAdjustmentType: string
{
    case INVOICE = 'invoice';               // Generar factura por diferencia (upgrade)
    case CREDIT_NOTE = 'credit_note';       // Nota de crédito (downgrade)
    case WALLET_CREDIT = 'wallet_credit';   // Abonar a wallet (downgrade)
    case NONE = 'none';                     // Sin ajuste (cambio al ciclo siguiente)
}
```

---

## Fase 3: Entidades

### 3.1 Modelo `PlanChangeRequest`

**Archivo**: `Modules/Subscription/app/Entities/PlanChangeRequest.php`

Relaciones:
```php
public function subscription(): BelongsTo
public function customer(): BelongsTo
public function oldPlan(): BelongsTo  // Plan, 'old_plan_id'
public function newPlan(): BelongsTo  // Plan, 'new_plan_id'
public function approvedBy(): BelongsTo  // User
public function requestedBy(): BelongsTo  // User
```

Métodos:
```php
public function isUpgrade(): bool
public function isDowngrade(): bool
public function isPending(): bool
public function canBeExecuted(): bool  // approved + no ejecutado
public function canBeCancelled(): bool
```

Scopes:
```php
public function scopePending($query)
public function scopeScheduledFor($query, Carbon $date)
```

### 3.2 Actualizar `Subscription`

Agregar relación:
```php
public function planChangeRequests(): HasMany
public function activePlanChangeRequest(): HasOne  // status in (pending, approved, executing)
```

Agregar métodos:
```php
public function hasPendingPlanChange(): bool
public function isWithinMinimumStay(): bool
public function canChangePlan(): bool  // active + no pending change + no minimum stay block
```

---

## Fase 4: DTOs

**Archivo**: `Modules/Subscription/app/DTOs/RequestPlanChangeDTO.php`

```php
final readonly class RequestPlanChangeDTO
{
    public function __construct(
        public int $subscriptionId,
        public int $newPlanId,
        public string $effectiveMode = 'immediate',  // immediate, next_cycle, scheduled
        public ?Carbon $scheduledFor = null,
        public ?string $notes = null,
        public ?int $requestedBy = null,
    ) {}
}
```

**Archivo**: `Modules/Subscription/app/DTOs/PlanChangeCalculation.php`

```php
final readonly class PlanChangeCalculation
{
    public function __construct(
        public string $changeType,          // upgrade, downgrade, lateral
        public float $oldMonthlyPrice,
        public float $newMonthlyPrice,
        public int $daysRemainingInCycle,
        public int $totalDaysInCycle,
        public float $prorateCredit,        // crédito por plan actual no consumido
        public float $prorateDebit,         // cargo por plan nuevo restante
        public float $netDifference,        // debit - credit
        public string $billingAdjustmentType,
        public array $oldPlanSnapshot,
        public array $newPlanSnapshot,
    ) {}
}
```

---

## Fase 5: Servicios

### 5.1 PlanChangeService

**Archivo**: `Modules/Subscription/app/Services/PlanChangeService.php`

```php
class PlanChangeService
{
    public function __construct(
        protected PlanChangeCalculator $calculator,
        protected SubscriptionContractService $contractService,
        protected ProvisioningService $provisioningService,
        protected InvoiceService $invoiceService,
        protected WalletService $walletService,
    ) {}

    /**
     * Solicitar cambio de plan.
     */
    public function request(RequestPlanChangeDTO $dto): PlanChangeRequest;

    /**
     * Aprobar solicitud (si requiere aprobación).
     */
    public function approve(PlanChangeRequest $request, int $approvedBy): PlanChangeRequest;

    /**
     * Rechazar solicitud.
     */
    public function reject(PlanChangeRequest $request, string $reason, int $rejectedBy): PlanChangeRequest;

    /**
     * Ejecutar el cambio de plan.
     */
    public function execute(PlanChangeRequest $request): PlanChangeRequest;

    /**
     * Cancelar solicitud pendiente.
     */
    public function cancel(PlanChangeRequest $request, string $reason): PlanChangeRequest;

    /**
     * Preview: calcular impacto sin ejecutar.
     */
    public function preview(int $subscriptionId, int $newPlanId): PlanChangeCalculation;

    /**
     * Procesar cambios programados cuya fecha llegó.
     */
    public function processScheduledChanges(): int;
}
```

**Lógica de `request`**:
```
1. Validar elegibilidad:
   - Suscripción activa
   - No tiene pending plan change
   - No está en periodo de permanencia mínima (o es upgrade)
   - Plan destino existe y está activo
   - Plan destino != plan actual
2. Calcular impacto económico via calculator
3. Verificar factibilidad técnica del plan destino
4. Determinar si requiere aprobación:
   - downgrade durante promoción → requiere
   - cambio que reduce ingresos → según política
5. Crear PlanChangeRequest con snapshots
6. Marcar subscription.has_pending_plan_change = true
7. Si no requiere aprobación y effective_mode = immediate → execute()
8. Si requiere aprobación → status = pending, disparar PlanChangeRequested
9. Si effective_mode = next_cycle o scheduled → status = approved, programar
```

**Lógica de `execute`**:
```
1. Verificar que request canBeExecuted()
2. Marcar status = 'executing'
3. DB::transaction:
   a. Actualizar suscripción:
      - plan_id = new_plan_id
      - monthly_price = new_monthly_price
      - has_pending_plan_change = false
      - last_plan_change_at = now()
   b. Actualizar commercial_snapshot via contractService
   c. Manejar impacto financiero:
      - Si upgrade inmediato (net_difference > 0):
        * Generar factura por diferencia (type = 'adjustment')
      - Si downgrade inmediato (net_difference < 0):
        * Abonar |net_difference| a wallet (o nota de crédito)
      - Si next_cycle → ningún ajuste financiero
   d. Reprovisionar servicio en red:
      - Actualizar perfil de velocidad en RouterOS/OLT
      - Actualizar IP pool si cambia
   e. Si reprovisión falla:
      - Rollback de suscripción al plan anterior
      - Marcar request como failed
      - Disparar PlanChangeProvisioningFailed
      - Retornar
   f. Registrar en SubscriptionStatusHistory
   g. Marcar request como completed
4. Disparar PlanChangeExecuted + SubscriptionPlanChanged
```

### 5.2 PlanChangeCalculator

**Archivo**: `Modules/Subscription/app/Services/PlanChangeCalculator.php`

```php
class PlanChangeCalculator
{
    /**
     * Calcula el impacto financiero del cambio.
     */
    public function calculate(
        Subscription $subscription,
        Plan $newPlan,
        string $effectiveMode = 'immediate',
    ): PlanChangeCalculation;

    /**
     * Calcula los días restantes en el ciclo actual.
     */
    protected function getDaysRemainingInCycle(Subscription $subscription): int;

    /**
     * Genera snapshot del plan.
     */
    protected function buildPlanSnapshot(Plan $plan, Subscription $subscription): array;

    /**
     * Determina el tipo de ajuste financiero.
     */
    protected function determineBillingAdjustment(
        string $changeType,
        string $effectiveMode,
        float $netDifference,
    ): string;
}
```

**Lógica de `calculate`**:
```
1. Obtener datos actuales:
   - oldPrice = subscription.monthly_price (congelado)
   - newPrice = newPlan.price
   - changeType = PlanChangeType::determine(oldPrice, newPrice)

2. Si effective_mode = next_cycle:
   - prorateCredit = 0
   - prorateDebit = 0
   - netDifference = 0
   - billingAdjustment = NONE

3. Si effective_mode = immediate:
   - daysRemaining = getDaysRemainingInCycle()
   - totalDays = daysInMonth (o período de facturación)
   - dailyOldRate = oldPrice / totalDays
   - dailyNewRate = newPrice / totalDays
   - prorateCredit = dailyOldRate * daysRemaining  (lo que no consumió)
   - prorateDebit = dailyNewRate * daysRemaining   (lo que consumirá)
   - netDifference = prorateDebit - prorateCredit
   - billingAdjustment = determineBillingAdjustment(...)

4. Construir snapshots de plan anterior y nuevo
5. Retornar PlanChangeCalculation
```

### 5.3 Validación de Factibilidad Técnica

Reutilizar `Modules/Network/app/Services/ProvisioningService.php`:

Agregar método:
```php
public function validatePlanFeasibility(Subscription $subscription, Plan $newPlan): array
{
    // Verificar que el pool de IP del nuevo plan tiene disponibilidad
    // Verificar que el equipo del cliente soporta el nuevo perfil
    // Verificar que la OLT soporta el bandwidth
    return ['feasible' => true/false, 'conditions' => [], 'reason' => null];
}
```

---

## Fase 6: Job para Cambios Programados

**Archivo**: `Modules/Subscription/app/Jobs/ProcessScheduledPlanChangesJob.php`

```php
class ProcessScheduledPlanChangesJob implements ShouldQueue
{
    public function handle(PlanChangeService $planChangeService): void
    {
        $planChangeService->processScheduledChanges();
    }
}
```

**Lógica de `processScheduledChanges`**:
```
1. Obtener PlanChangeRequests donde:
   - status = 'approved'
   - effective_mode in ('next_cycle', 'scheduled')
   - scheduled_for <= today (o billing_day = today para next_cycle)
2. Para cada uno → execute()
3. Retornar cantidad procesada
```

**Artisan Command**:

**Archivo**: `Modules/Subscription/app/Console/ProcessPlanChangesCommand.php`

```php
protected $signature = 'subscription:process-plan-changes
    {--sync : Ejecutar sincrónicamente}';
```

**Scheduler**:
```php
$schedule->command('subscription:process-plan-changes --sync')->dailyAt('00:30');
```

---

## Fase 7: Eventos y Listeners

### 7.1 Eventos

| Evento | Datos |
|---|---|
| `PlanChangeRequested` | `PlanChangeRequest` |
| `PlanChangeApproved` | `PlanChangeRequest` |
| `PlanChangeRejected` | `PlanChangeRequest`, razón |
| `PlanChangeBilled` | `PlanChangeRequest`, `Invoice` o `WalletTransaction` |
| `PlanChangeExecuted` | `PlanChangeRequest` |
| `PlanChangeProvisioningFailed` | `PlanChangeRequest`, razón |
| `PlanChangeCancelled` | `PlanChangeRequest`, razón |
| `SubscriptionPlanChanged` | `Subscription`, old plan, new plan |

### 7.2 Listeners

| Listener | Escucha | Acción |
|---|---|---|
| `NotifyPlanChangeRequest` | `PlanChangeRequested` | Notificar al aprobador si requiere aprobación |
| `NotifyPlanChangeResult` | `PlanChangeExecuted` | Notificar al cliente del cambio exitoso |
| `NotifyPlanChangeFailed` | `PlanChangeProvisioningFailed` | Notificar fallo + crear incidencia |
| `UpdateCommercialSnapshot` | `SubscriptionPlanChanged` | Regenerar `commercial_snapshot` |
| `LogPlanChange` | `SubscriptionPlanChanged` | Registrar en `SubscriptionStatusHistory` |

---

## Fase 8: API Endpoints

### 8.1 Actualizar SubscriptionController

Reemplazar el `changePlan()` actual con un flujo más robusto:

| Método | Ruta | Acción |
|---|---|---|
| `POST` | `/api/subscriptions/{id}/plan-change/preview` | Preview del impacto sin ejecutar |
| `POST` | `/api/subscriptions/{id}/plan-change/request` | Solicitar cambio de plan |
| `GET` | `/api/subscriptions/{id}/plan-change/history` | Historial de cambios |
| `POST` | `/api/plan-changes/{id}/approve` | Aprobar solicitud |
| `POST` | `/api/plan-changes/{id}/reject` | Rechazar solicitud |
| `POST` | `/api/plan-changes/{id}/execute` | Ejecutar manualmente |
| `POST` | `/api/plan-changes/{id}/cancel` | Cancelar solicitud |

**Controller nuevo**: `Modules/Subscription/app/Http/Controllers/PlanChangeController.php`

---

## Fase 9: Configuración

**Agregar a `Modules/Subscription/config/config.php`**:

```php
'plan_change' => [
    'default_effective_mode' => env('PLAN_CHANGE_MODE', 'immediate'),
    'downgrade_mode' => env('PLAN_CHANGE_DOWNGRADE_MODE', 'next_cycle'),  // immediate | next_cycle
    'upgrade_mode' => env('PLAN_CHANGE_UPGRADE_MODE', 'immediate'),
    'require_approval_for_downgrade' => env('PLAN_CHANGE_APPROVAL_DOWNGRADE', false),
    'require_approval_during_promotion' => env('PLAN_CHANGE_APPROVAL_PROMO', true),
    'minimum_stay_days' => env('PLAN_CHANGE_MIN_STAY', 0),  // 0 = sin restricción
    'downgrade_credit_to' => env('PLAN_CHANGE_CREDIT_TO', 'wallet'),  // wallet | credit_note
    'max_pending_requests' => 1,
    'prorate_calculation' => 'daily',  // daily | proportional
],
```

---

## Fase 10: Tests

### Tests Unitarios

**`PlanChangeCalculatorTest`**:
- Upgrade inmediato calcula débito - crédito correctamente
- Downgrade inmediato calcula crédito positivo
- Cambio lateral → net_difference = 0
- next_cycle → sin prorrata
- Días restantes se calculan correctamente según billing_day
- Snapshots contienen datos completos del plan

**`PlanChangeServiceTest`**:
- Request exitoso para upgrade
- Request bloqueado si hay pending change
- Request bloqueado si está en permanencia mínima (excepto upgrade)
- Downgrade durante promoción requiere aprobación
- Execute actualiza plan_id y monthly_price
- Execute genera factura de ajuste en upgrade
- Execute acredita wallet en downgrade
- Execute hace rollback si falla reprovisión
- Cancel libera has_pending_plan_change

### Tests de Integración

**`PlanChangeFlowTest`**:

```php
/** @test */
public function upgrade_immediate_generates_adjustment_invoice()
{
    // Suscripción plan $50 → solicitar plan $80
    // Assert: PlanChangeRequest creado como 'upgrade'
    // Assert: Se ejecuta inmediatamente
    // Assert: Invoice de ajuste generada por la diferencia prorrateada
    // Assert: subscription.plan_id actualizado
    // Assert: subscription.monthly_price = 80
    // Assert: reprovisión ejecutada
}

/** @test */
public function downgrade_next_cycle_credits_wallet_at_billing()
{
    // Suscripción plan $80 → solicitar plan $50 (next_cycle)
    // Assert: PlanChangeRequest approved + scheduled
    // Assert: No cambia nada todavía
    // Avanzar al billing_day
    // Ejecutar processScheduledChanges()
    // Assert: Plan cambiado
    // Assert: Wallet creditada si había prorrata
}

/** @test */
public function provisioning_failure_rolls_back()
{
    // Mock: provisioningService falla
    // Solicitar cambio
    // Assert: subscription mantiene plan original
    // Assert: PlanChangeRequest status = 'failed'
    // Assert: PlanChangeProvisioningFailed disparado
}

/** @test */
public function concurrent_changes_blocked()
{
    // Crear PlanChangeRequest pendiente
    // Intentar crear otra
    // Assert: DomainException
}
```

---

## Refactoring del changePlan() Existente

El método actual `SubscriptionService::changePlan()` se debe deprecar y redirigir al nuevo flujo:

```php
// En SubscriptionService
public function changePlan(Subscription $subscription, int $newPlanId, bool $immediate = true): Subscription
{
    // Redirigir al nuevo servicio
    $request = $this->planChangeService->request(new RequestPlanChangeDTO(
        subscriptionId: $subscription->id,
        newPlanId: $newPlanId,
        effectiveMode: $immediate ? 'immediate' : 'next_cycle',
    ));

    return $subscription->fresh(['plan']);
}
```

---

## Resumen de Archivos

### Nuevos (~20)

| Tipo | Cantidad |
|---|---|
| Migraciones | 2 |
| Enums | 4 |
| Entidades | 1 |
| DTOs | 2 |
| Servicios | 2 |
| Job | 1 |
| Command | 1 |
| Eventos | 8 |
| Listeners | 5 |
| Controller | 1 |

### A Modificar (~5)

- `Subscription.php` — agregar relaciones, métodos, fillable
- `SubscriptionService.php` — redirigir `changePlan()` al nuevo flujo
- `ProvisioningService.php` — agregar `validatePlanFeasibility()`
- `SubscriptionServiceProvider.php` — registrar command y schedule
- `Subscription/config/config.php` — agregar sección plan_change
- `Subscription/routes/api.php` — agregar rutas de plan change

---

## Criterios de Aceptación

1. El cambio de plan se registra como solicitud formal con snapshots
2. Upgrade inmediato genera factura de ajuste por la prorrata
3. Downgrade next_cycle se programa y ejecuta en el próximo billing_day
4. La prorrata se calcula correctamente: crédito del plan viejo + débito del nuevo
5. Si la reprovisión de red falla, se hace rollback y no se cobra
6. Solo puede existir 1 solicitud de cambio abierta por suscripción
7. Downgrade durante promoción requiere aprobación
8. Los cambios programados se procesan automáticamente por job diario
9. La API de preview permite al cliente ver el impacto antes de confirmar
10. Todo cambio queda trazado con snapshots del plan anterior y nuevo
11. El `commercial_snapshot` de la suscripción se regenera tras el cambio
