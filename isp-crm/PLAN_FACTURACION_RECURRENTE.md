# Plan de Implementación: Facturación Recurrente

## Estado Actual del Proyecto

### Ya Implementado

| Componente | Ubicación | Estado |
|---|---|---|
| `Invoice` modelo | `Modules/Finance/app/Entities/Invoice.php` | Básico, sin campos de período |
| `InvoiceItem` modelo | `Modules/Finance/app/Entities/InvoiceItem.php` | Básico, sin campos de tipo |
| `Wallet` modelo | `Modules/Finance/app/Entities/Wallet.php` | Completo |
| `InvoiceService` | `Modules/Finance/app/Services/InvoiceService.php` | Solo factura inicial + prorrata |
| `WalletService` | `Modules/Finance/app/Services/WalletService.php` | Completo |
| `GenerateInitialInvoice` listener | `Modules/Finance/app/Listeners/GenerateInitialInvoice.php` | Escucha `SubscriptionActivated` |
| `CreateWalletForCustomer` listener | `Modules/Finance/app/Listeners/CreateWalletForCustomer.php` | Completo |
| Eventos `InitialInvoiceGenerated`, `InvoicePaymentReceived` | `Modules/Finance/app/Events/` | Completo |
| `Subscription` con `billing_day`, `billing_cycle`, `commercial_snapshot` | `Modules/Subscription/` | Completo |
| `SubscriptionContractService` con snapshot comercial | `Modules/Subscription/app/Services/` | Completo |
| `ActivationService` con aprovisionamiento | `Modules/Subscription/app/Services/` | Completo |
| `BillingCycle` enum (monthly, bimonthly, quarterly, annual) | `Modules/Subscription/app/Enums/` | Completo |

### Falta Implementar

1. Campos de período y trazabilidad en `invoices` e `invoice_items`
2. Tabla `billing_job_runs` para rastrear ejecuciones del job
3. Tabla `billing_incidents` para omisiones y fallos por suscripción
4. Enums: `InvoiceType`, `InvoiceStatus`, `InvoiceItemType`, `GenerationSource`
5. DTO `BillingContext` para agregar datos de cálculo
6. `RecurringBillingService` con lógica de facturación recurrente
7. Job `GenerateMonthlyInvoicesJob`
8. Command `GenerateInvoicesCommand`
9. Eventos de dominio faltantes del ciclo de facturación
10. Listeners para los nuevos eventos
11. Registro en el Scheduler de Laravel
12. Tests unitarios y de integración

---

## Fase 1: Migraciones y Enums

### 1.1 Migración: Agregar campos de período a `invoices`

**Archivo**: `Modules/Finance/database/migrations/2026_03_28_130000_add_billing_period_fields_to_invoices_table.php`

Campos nuevos:
- `billing_period` varchar(7) nullable — formato `YYYY-MM`, clave de idempotencia junto con `subscription_id` + `type`
- `period_start` date nullable
- `period_end` date nullable
- `calculation_snapshot` json nullable — copia del cálculo completo para auditoría
- `generation_source` varchar(20) default `'scheduled'` — `scheduled`, `manual`, `adjustment`, `migration`
- `external_tax_status` varchar(30) nullable — `pending`, `submitted`, `accepted`, `rejected`, `not_required`
- `issued_by_job_run_id` bigint unsigned nullable FK → `billing_job_runs`

Índice compuesto único: `subscription_id + billing_period + type` (para idempotencia)

### 1.2 Migración: Agregar campos tipificados a `invoice_items`

**Archivo**: `Modules/Finance/database/migrations/2026_03_28_130100_add_type_fields_to_invoice_items_table.php`

Campos nuevos:
- `code` varchar(30) nullable — código interno del concepto
- `type` varchar(30) default `'service'` — `service`, `addon`, `discount`, `tax`, `proration`, `adjustment`
- `billing_period_start` date nullable
- `billing_period_end` date nullable
- `source_reference` varchar(100) nullable — referencia al origen (ej: `addon:5`, `promotion:3`)

### 1.3 Migración: Crear tabla `billing_job_runs`

**Archivo**: `Modules/Finance/database/migrations/2026_03_28_130200_create_billing_job_runs_table.php`

```
billing_job_runs
├── id
├── uuid unique
├── billing_period varchar(7)        — YYYY-MM
├── started_at timestamp
├── completed_at timestamp nullable
├── status varchar(20)               — running, completed, completed_with_errors, failed
├── total_eligible int default 0
├── total_processed int default 0
├── total_invoiced int default 0
├── total_skipped int default 0
├── total_failed int default 0
├── metadata json nullable            — detalles adicionales, duración, etc.
├── triggered_by varchar(20)          — scheduler, manual, artisan
├── user_id FK nullable → users
├── timestamps
```

### 1.4 Migración: Crear tabla `billing_incidents`

**Archivo**: `Modules/Finance/database/migrations/2026_03_28_130300_create_billing_incidents_table.php`

```
billing_incidents
├── id
├── billing_job_run_id FK → billing_job_runs
├── subscription_id FK → subscriptions
├── customer_id FK → customers
├── incident_type varchar(30)        — skipped, failed, duplicate, data_incomplete, tax_failed
├── reason text
├── metadata json nullable
├── resolved_at timestamp nullable
├── resolved_by FK nullable → users
├── timestamps
```

Índice: `billing_job_run_id + subscription_id`

### 1.5 Enums

**Archivo**: `Modules/Finance/app/Enums/InvoiceType.php`
```php
enum InvoiceType: string
{
    case INITIAL = 'initial';
    case MONTHLY = 'monthly';
    case ADJUSTMENT = 'adjustment';
    case CREDIT_NOTE = 'credit_note';
    case PROFORMA = 'proforma';
}
```

**Archivo**: `Modules/Finance/app/Enums/InvoiceStatus.php`
```php
enum InvoiceStatus: string
{
    case DRAFT = 'draft';
    case ISSUED = 'issued';
    case SENT = 'sent';
    case PAID = 'paid';
    case PARTIALLY_PAID = 'partially_paid';
    case OVERDUE = 'overdue';
    case CANCELLED = 'cancelled';
    case PENDING_TAX_SUBMISSION = 'pending_tax_submission';
}
```

**Archivo**: `Modules/Finance/app/Enums/InvoiceItemType.php`
```php
enum InvoiceItemType: string
{
    case SERVICE = 'service';
    case ADDON = 'addon';
    case DISCOUNT = 'discount';
    case TAX = 'tax';
    case PRORATION = 'proration';
    case ADJUSTMENT = 'adjustment';
    case INSTALLATION = 'installation';
}
```

**Archivo**: `Modules/Finance/app/Enums/GenerationSource.php`
```php
enum GenerationSource: string
{
    case SCHEDULED = 'scheduled';
    case MANUAL = 'manual';
    case ADJUSTMENT = 'adjustment';
    case MIGRATION = 'migration';
}
```

**Archivo**: `Modules/Finance/app/Enums/BillingIncidentType.php`
```php
enum BillingIncidentType: string
{
    case SKIPPED = 'skipped';
    case FAILED = 'failed';
    case DUPLICATE = 'duplicate';
    case DATA_INCOMPLETE = 'data_incomplete';
    case TAX_FAILED = 'tax_failed';
    case SUSPENDED_NO_CHARGE = 'suspended_no_charge';
}
```

---

## Fase 2: Entidades Nuevas

### 2.1 Modelo `BillingJobRun`

**Archivo**: `Modules/Finance/app/Entities/BillingJobRun.php`

Relaciones:
- `hasMany(BillingIncident::class)`
- `hasMany(Invoice::class, 'issued_by_job_run_id')`

Métodos:
- `markCompleted()` — actualiza `completed_at`, `status` y contadores finales
- `markFailed(string $reason)` — marca como fallo global
- `incrementProcessed()`, `incrementInvoiced()`, `incrementSkipped()`, `incrementFailed()`

### 2.2 Modelo `BillingIncident`

**Archivo**: `Modules/Finance/app/Entities/BillingIncident.php`

Relaciones:
- `belongsTo(BillingJobRun::class)`
- `belongsTo(Subscription::class)`
- `belongsTo(Customer::class)`

### 2.3 Actualizar modelo `Invoice`

Agregar a `$fillable`:
- `billing_period`, `period_start`, `period_end`, `calculation_snapshot`, `generation_source`, `external_tax_status`, `issued_by_job_run_id`

Agregar a `$casts`:
- `period_start` → `date`, `period_end` → `date`, `calculation_snapshot` → `array`
- `type` → `InvoiceType::class`, `status` → `InvoiceStatus::class`, `generation_source` → `GenerationSource::class`

Agregar relación:
- `belongsTo(BillingJobRun::class, 'issued_by_job_run_id')`

Agregar scope:
- `scopeForPeriod($query, string $billingPeriod)` — filtra por `billing_period`

### 2.4 Actualizar modelo `InvoiceItem`

Agregar a `$fillable`:
- `code`, `type`, `billing_period_start`, `billing_period_end`, `source_reference`

Agregar a `$casts`:
- `type` → `InvoiceItemType::class`, `billing_period_start` → `date`, `billing_period_end` → `date`

### 2.5 Agregar relación `invoices()` a `Subscription`

En `Modules/Subscription/app/Entities/Subscription.php` agregar:
```php
public function invoices(): HasMany
{
    return $this->hasMany(\Modules\Finance\Entities\Invoice::class);
}
```

Y scope auxiliar:
```php
public function scopeBillableToday($query)
{
    return $query->where('status', SubscriptionStatus::ACTIVE)
        ->where('billing_day', now()->day);
}
```

---

## Fase 3: DTO BillingContext y Calculadora

### 3.1 BillingContext

**Archivo**: `Modules/Finance/app/DTOs/BillingContext.php`

```php
final readonly class BillingContext
{
    public function __construct(
        public Subscription $subscription,
        public string $billingPeriod,       // 'YYYY-MM'
        public Carbon $periodStart,
        public Carbon $periodEnd,
        public float $basePrice,            // precio mensual pactado
        public float $effectivePrice,       // después de descuento
        public array $activeAddons,         // [{id, name, price}]
        public float $addonsTotal,
        public float $discountAmount,       // monto del descuento
        public ?float $discountPercentage,
        public int $discountMonthsRemaining,
        public float $subtotal,
        public float $taxAmount,
        public float $total,
        public array $commercialSnapshot,   // snapshot de la suscripción
        public string $generationSource,
        public ?int $jobRunId = null,
    ) {}

    public function toCalculationSnapshot(): array
    {
        return [
            'billing_period' => $this->billingPeriod,
            'period_start' => $this->periodStart->toDateString(),
            'period_end' => $this->periodEnd->toDateString(),
            'base_price' => $this->basePrice,
            'effective_price' => $this->effectivePrice,
            'addons' => $this->activeAddons,
            'addons_total' => $this->addonsTotal,
            'discount_amount' => $this->discountAmount,
            'discount_percentage' => $this->discountPercentage,
            'discount_months_remaining' => $this->discountMonthsRemaining,
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->taxAmount,
            'total' => $this->total,
            'calculated_at' => now()->toIso8601String(),
        ];
    }
}
```

### 3.2 BillingCalculator

**Archivo**: `Modules/Finance/app/Services/BillingCalculator.php`

```php
class BillingCalculator
{
    public function buildContext(
        Subscription $subscription,
        string $billingPeriod,
        string $generationSource = 'scheduled',
        ?int $jobRunId = null,
    ): BillingContext;

    protected function calculatePeriodDates(Subscription $sub, string $period): array;
    protected function calculateDiscount(Subscription $sub): array;
    protected function calculateTax(float $subtotal, Subscription $sub): float;
    protected function resolveActiveAddons(Subscription $sub, Carbon $periodStart): array;
}
```

Lógica de `buildContext`:
1. Calcular `periodStart` y `periodEnd` a partir de `billing_day` y `billingPeriod`
2. Leer `monthly_price` de la suscripción (ya congelado, no del catálogo)
3. Calcular descuento si `discount_months_remaining > 0`
4. Filtrar addons activos cuya `start_date <= periodStart` y `end_date IS NULL or >= periodEnd`
5. Sumar subtotal = effectivePrice + addonsTotal
6. Calcular impuestos según configuración (placeholder configurable)
7. Retornar `BillingContext`

---

## Fase 4: RecurringBillingService

### 4.1 Servicio Principal

**Archivo**: `Modules/Finance/app/Services/RecurringBillingService.php`

```php
class RecurringBillingService
{
    public function __construct(
        protected BillingCalculator $calculator,
        protected InvoiceService $invoiceService,
    ) {}

    /**
     * Punto de entrada principal. Factura todas las suscripciones elegibles.
     */
    public function runBillingCycle(
        string $billingPeriod,
        string $triggeredBy = 'scheduler',
        ?int $userId = null,
    ): BillingJobRun;

    /**
     * Factura una sola suscripción. Reutilizable para emisión manual.
     */
    public function billSubscription(
        Subscription $subscription,
        string $billingPeriod,
        string $generationSource = 'scheduled',
        ?int $jobRunId = null,
    ): Invoice;

    /**
     * Determina si una suscripción es elegible para facturación en el período dado.
     */
    public function isEligible(Subscription $subscription, string $billingPeriod): bool;

    /**
     * Obtiene suscripciones elegibles para un período.
     */
    protected function getEligibleSubscriptions(string $billingPeriod): LazyCollection;

    /**
     * Verifica que no exista factura previa válida del mismo ciclo.
     */
    protected function hasExistingInvoice(int $subscriptionId, string $billingPeriod): bool;
}
```

**Lógica de `runBillingCycle`**:
```
1. Crear BillingJobRun con status 'running'
2. Obtener suscripciones elegibles via getEligibleSubscriptions()
3. Por cada suscripción (chunked):
   a. Verificar elegibilidad completa
   b. Si no elegible → crear BillingIncident(skipped) + incrementSkipped()
   c. Si elegible:
      i.   Construir BillingContext via calculator
      ii.  Intentar crear factura via billSubscription()
      iii. Si éxito → incrementInvoiced()
      iv.  Si fallo → crear BillingIncident(failed) + incrementFailed()
   d. incrementProcessed()
4. Marcar BillingJobRun como completada
5. Disparar InvoiceBatchGenerated
6. Retornar BillingJobRun
```

**Lógica de `getEligibleSubscriptions`**:
```php
Subscription::query()
    ->where('status', SubscriptionStatus::ACTIVE)
    ->where('billing_day', Carbon::parse($billingPeriod . '-01')->daysInMonth >= now()->day ? now()->day : ...)
    ->whereNotNull('start_date')
    ->where('start_date', '<=', $periodEnd)
    ->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', $periodStart))
    ->whereDoesntHave('invoices', fn($q) =>
        $q->where('billing_period', $billingPeriod)
          ->where('type', InvoiceType::MONTHLY)
          ->whereNotIn('status', ['cancelled'])
    )
    ->with(['customer', 'addons', 'plan'])
    ->lazy(100);
```

**Lógica de `isEligible`**:
```
- status es ACTIVE
- start_date existe y es <= fin del período
- end_date es null o >= inicio del período
- billing_day coincide con el día de ejecución (o regla de mes corto)
- no existe factura válida del mismo período
- customer existe y no está eliminado
- datos fiscales mínimos presentes (si aplica)
```

### 4.2 Extender InvoiceService

Agregar método `generateRecurringInvoice(BillingContext $context): Invoice` a `InvoiceService`:

```php
public function generateRecurringInvoice(BillingContext $context): Invoice
{
    // Idempotencia: verificar que no exista
    $existing = Invoice::where('subscription_id', $context->subscription->id)
        ->where('billing_period', $context->billingPeriod)
        ->where('type', InvoiceType::MONTHLY)
        ->whereNot('status', InvoiceStatus::CANCELLED)
        ->first();

    if ($existing) {
        return $existing->load('items');
    }

    return DB::transaction(function () use ($context) {
        $invoice = Invoice::create([
            'customer_id' => $context->subscription->customer_id,
            'subscription_id' => $context->subscription->id,
            'invoice_number' => $this->generateInvoiceNumber(),
            'type' => InvoiceType::MONTHLY,
            'billing_period' => $context->billingPeriod,
            'period_start' => $context->periodStart,
            'period_end' => $context->periodEnd,
            'subtotal' => $context->subtotal,
            'tax' => $context->taxAmount,
            'total' => $context->total,
            'due_date' => $context->periodStart->copy()->addDays($gracePeriod),
            'status' => InvoiceStatus::ISSUED,
            'calculation_snapshot' => $context->toCalculationSnapshot(),
            'generation_source' => $context->generationSource,
            'issued_by_job_run_id' => $context->jobRunId,
        ]);

        // Item: servicio mensual
        $invoice->items()->create([
            'code' => 'SVC-MONTHLY',
            'type' => InvoiceItemType::SERVICE,
            'concept' => 'Servicio mensual',
            'description' => $context->subscription->plan->name ?? 'Plan de internet',
            'quantity' => 1,
            'unit_price' => $context->basePrice,
            'subtotal' => $context->basePrice,
            'tax' => 0,
            'billing_period_start' => $context->periodStart,
            'billing_period_end' => $context->periodEnd,
            'source_reference' => 'plan:' . $context->subscription->plan_id,
        ]);

        // Items: addons
        foreach ($context->activeAddons as $addon) {
            $invoice->items()->create([
                'code' => 'ADDON-' . $addon['id'],
                'type' => InvoiceItemType::ADDON,
                'concept' => 'Addon: ' . $addon['name'],
                'description' => $addon['name'],
                'quantity' => 1,
                'unit_price' => $addon['price'],
                'subtotal' => $addon['price'],
                'tax' => 0,
                'billing_period_start' => $context->periodStart,
                'billing_period_end' => $context->periodEnd,
                'source_reference' => 'addon:' . $addon['id'],
            ]);
        }

        // Item: descuento (como item negativo)
        if ($context->discountAmount > 0) {
            $invoice->items()->create([
                'code' => 'DISC-PROMO',
                'type' => InvoiceItemType::DISCOUNT,
                'concept' => 'Descuento promocional',
                'description' => $context->discountPercentage . '% descuento',
                'quantity' => 1,
                'unit_price' => -$context->discountAmount,
                'subtotal' => -$context->discountAmount,
                'tax' => 0,
                'billing_period_start' => $context->periodStart,
                'billing_period_end' => $context->periodEnd,
                'source_reference' => 'promotion:' . ($context->subscription->promotion_id ?? 'none'),
            ]);
        }

        // Item: impuestos
        if ($context->taxAmount > 0) {
            $invoice->items()->create([
                'code' => 'TAX-IVA',
                'type' => InvoiceItemType::TAX,
                'concept' => 'Impuesto',
                'description' => 'IVA / IGV',
                'quantity' => 1,
                'unit_price' => $context->taxAmount,
                'subtotal' => $context->taxAmount,
                'tax' => 0,
                'billing_period_start' => $context->periodStart,
                'billing_period_end' => $context->periodEnd,
            ]);
        }

        // Decrementar meses de descuento restantes
        if ($context->discountMonthsRemaining > 0) {
            $context->subscription->decrement('discount_months_remaining');
        }

        event(new InvoiceGenerated($invoice->fresh('items')));

        return $invoice->fresh('items');
    });
}
```

---

## Fase 5: Job y Command

### 5.1 Job

**Archivo**: `Modules/Finance/app/Jobs/GenerateMonthlyInvoicesJob.php`

```php
class GenerateMonthlyInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 600; // 10 minutos

    public function __construct(
        public readonly string $billingPeriod,
        public readonly string $triggeredBy = 'scheduler',
        public readonly ?int $userId = null,
    ) {}

    public function handle(RecurringBillingService $billingService): void
    {
        $billingService->runBillingCycle(
            billingPeriod: $this->billingPeriod,
            triggeredBy: $this->triggeredBy,
            userId: $this->userId,
        );
    }
}
```

### 5.2 Artisan Command

**Archivo**: `Modules/Finance/app/Console/GenerateInvoicesCommand.php`

```php
class GenerateInvoicesCommand extends Command
{
    protected $signature = 'finance:generate-invoices
        {--period= : Período en formato YYYY-MM (default: mes actual)}
        {--sync : Ejecutar de forma síncrona en vez de via queue}
        {--subscription= : Facturar una sola suscripción por ID}';

    protected $description = 'Genera facturas recurrentes para el período indicado';

    public function handle(RecurringBillingService $billingService): int
    {
        $period = $this->option('period') ?? now()->format('Y-m');

        if ($subscriptionId = $this->option('subscription')) {
            // Facturación individual
            $subscription = Subscription::findOrFail($subscriptionId);
            $invoice = $billingService->billSubscription($subscription, $period, 'manual');
            $this->info("Factura {$invoice->invoice_number} generada.");
            return self::SUCCESS;
        }

        if ($this->option('sync')) {
            $jobRun = $billingService->runBillingCycle($period, 'artisan', auth()->id());
            $this->printSummary($jobRun);
            return self::SUCCESS;
        }

        GenerateMonthlyInvoicesJob::dispatch($period, 'artisan', auth()->id());
        $this->info("Job de facturación encolado para período {$period}.");
        return self::SUCCESS;
    }
}
```

### 5.3 Scheduler

En `Modules/Finance/app/Providers/FinanceServiceProvider.php`, registrar el schedule:

```php
// En boot() o via schedule()
$this->app->booted(function () {
    $schedule = $this->app->make(Schedule::class);
    $schedule->command('finance:generate-invoices --sync')
        ->dailyAt('00:01')
        ->withoutOverlapping()
        ->onOneServer()
        ->appendOutputTo(storage_path('logs/billing.log'));
});
```

---

## Fase 6: Eventos y Listeners

### 6.1 Eventos Nuevos

| Evento | Descripción | Datos |
|---|---|---|
| `InvoiceGenerationStarted` | Inicio de corrida de facturación | `BillingJobRun` |
| `InvoiceGenerated` | Factura recurrente emitida | `Invoice` |
| `InvoiceGenerationSkipped` | Suscripción omitida | `Subscription`, `reason`, `BillingIncident` |
| `InvoiceGenerationFailed` | Fallo al facturar suscripción | `Subscription`, `exception`, `BillingIncident` |
| `InvoiceBatchCompleted` | Corrida de facturación terminada | `BillingJobRun` |
| `InvoiceDelivered` | Factura enviada al cliente | `Invoice`, `channel` |
| `InvoiceTaxSubmissionFailed` | Fallo en integración fiscal | `Invoice`, `reason` |

**Archivos**: `Modules/Finance/app/Events/InvoiceGenerated.php`, etc.

### 6.2 Listeners

| Listener | Escucha | Acción |
|---|---|---|
| `SendInvoiceNotification` | `InvoiceGenerated` | Enviar email/SMS con la factura |
| `UpdateDebtAging` | `InvoiceGenerated` | Actualizar balance en Wallet |
| `SchedulePaymentReminders` | `InvoiceGenerated` | Programar recordatorios antes de vencimiento |
| `LogBillingCompletion` | `InvoiceBatchCompleted` | Log y notificación admin del resultado |
| `HandleTaxFailure` | `InvoiceTaxSubmissionFailed` | Crear incidencia y alerta |
| `DecrementDiscountMonths` | `InvoiceGenerated` | Ya integrado en el servicio, listener opcional para auditoría |

**Archivos**: `Modules/Finance/app/Listeners/SendInvoiceNotification.php`, etc.

### 6.3 Registrar en EventServiceProvider

**Archivo**: `Modules/Finance/app/Providers/EventServiceProvider.php`

```php
protected $listen = [
    InvoiceGenerated::class => [
        SendInvoiceNotification::class,
        UpdateDebtAging::class,
        SchedulePaymentReminders::class,
    ],
    InvoiceBatchCompleted::class => [
        LogBillingCompletion::class,
    ],
    InvoiceTaxSubmissionFailed::class => [
        HandleTaxFailure::class,
    ],
];
```

---

## Fase 7: Configuración

### 7.1 Config de Facturación

**Archivo**: `Modules/Finance/config/config.php` (actualizar)

```php
return [
    'billing' => [
        'grace_period_days' => env('BILLING_GRACE_PERIOD', 10),
        'tax_rate' => env('BILLING_TAX_RATE', 0.00),        // 0.18 para IGV
        'tax_enabled' => env('BILLING_TAX_ENABLED', false),
        'tax_name' => env('BILLING_TAX_NAME', 'IGV'),
        'external_tax_integration' => env('BILLING_EXTERNAL_TAX', false),
        'invoice_prefix' => env('BILLING_INVOICE_PREFIX', 'FAC'),
        'billing_policy' => env('BILLING_POLICY', 'advance'),  // advance | arrears
        'short_month_strategy' => env('BILLING_SHORT_MONTH', 'last_day'),  // last_day | skip | next_month
        'max_retry_attempts' => env('BILLING_MAX_RETRIES', 3),
        'notification_channels' => ['email'],                  // email, sms
    ],
];
```

---

## Fase 8: Tests

### 8.1 Tests Unitarios

**Archivo**: `Modules/Finance/tests/Unit/BillingCalculatorTest.php`
- Calcula precio base correctamente
- Aplica descuento porcentual
- No aplica descuento si `discount_months_remaining` es 0
- Incluye addons activos, excluye vencidos
- Calcula impuestos cuando están habilitados
- Calcula período correctamente para `billing_day` en mes corto

**Archivo**: `Modules/Finance/tests/Unit/RecurringBillingServiceTest.php`
- `isEligible` retorna true para suscripción activa con `billing_day` correcto
- `isEligible` retorna false para suscripción suspendida
- `isEligible` retorna false si ya existe factura del período
- `isEligible` retorna false si `start_date` es posterior al período
- `isEligible` retorna true si factura previa está cancelada
- `billSubscription` es idempotente (retorna factura existente sin duplicar)
- `billSubscription` decrementa `discount_months_remaining`

### 8.2 Tests de Integración

**Archivo**: `Modules/Finance/tests/Feature/RecurringBillingTest.php`

```php
/** @test */
public function run_billing_cycle_generates_invoices_for_eligible_subscriptions()
{
    // Crear 3 suscripciones activas con billing_day = hoy
    // Crear 1 suscripción suspendida
    // Crear 1 suscripción activa ya facturada
    // Ejecutar runBillingCycle()
    // Assert: 3 facturas generadas
    // Assert: 1 incident tipo 'skipped' (suspendida)
    // Assert: la ya facturada no se duplicó
    // Assert: BillingJobRun con contadores correctos
}

/** @test */
public function idempotent_billing_does_not_duplicate()
{
    // Crear suscripción y facturarla
    // Ejecutar de nuevo el ciclo
    // Assert: sigue siendo 1 sola factura
}

/** @test */
public function discount_months_decrement_on_billing()
{
    // Crear suscripción con discount_months_remaining = 3
    // Facturar
    // Assert: discount_months_remaining = 2
    // Assert: factura tiene item de descuento con monto negativo
}

/** @test */
public function billing_handles_subscription_failure_gracefully()
{
    // Crear suscripción con datos incompletos
    // Ejecutar ciclo
    // Assert: BillingIncident creada con tipo 'failed'
    // Assert: otras suscripciones se facturaron correctamente
}
```

---

## Fase 9: API Endpoints

### 9.1 Endpoints de Facturación

| Método | Ruta | Acción |
|---|---|---|
| `GET` | `/api/finance/invoices` | Listar facturas con filtros |
| `GET` | `/api/finance/invoices/{id}` | Detalle de factura con items |
| `POST` | `/api/finance/invoices/{id}/send` | Reenviar factura |
| `POST` | `/api/finance/invoices/{id}/pay` | Registrar pago |
| `POST` | `/api/finance/invoices/{id}/cancel` | Anular factura |
| `POST` | `/api/finance/billing/run` | Ejecutar facturación manual |
| `POST` | `/api/finance/billing/preview` | Preview sin emitir |
| `GET` | `/api/finance/billing/job-runs` | Historial de corridas |
| `GET` | `/api/finance/billing/job-runs/{id}` | Detalle con incidentes |
| `GET` | `/api/finance/billing/incidents` | Incidentes pendientes |
| `POST` | `/api/finance/billing/incidents/{id}/resolve` | Resolver incidente |

### 9.2 Controllers

**Archivo**: `Modules/Finance/app/Http/Controllers/InvoiceController.php`
**Archivo**: `Modules/Finance/app/Http/Controllers/BillingController.php`

---

## Orden de Implementación

```
Fase 1: Migraciones y Enums
   ↓
Fase 2: Entidades nuevas + actualizar Invoice/InvoiceItem/Subscription
   ↓
Fase 3: BillingContext DTO + BillingCalculator
   ↓
Fase 4: RecurringBillingService + extender InvoiceService
   ↓
Fase 5: Job + Command + Scheduler
   ↓
Fase 6: Eventos + Listeners
   ↓
Fase 7: Configuración
   ↓
Fase 8: Tests
   ↓
Fase 9: API + Controllers
```

---

## Resumen de Archivos a Crear/Modificar

### Archivos Nuevos (~25)

| Archivo | Tipo |
|---|---|
| Migración `add_billing_period_fields_to_invoices` | Migration |
| Migración `add_type_fields_to_invoice_items` | Migration |
| Migración `create_billing_job_runs` | Migration |
| Migración `create_billing_incidents` | Migration |
| `InvoiceType` | Enum |
| `InvoiceStatus` | Enum |
| `InvoiceItemType` | Enum |
| `GenerationSource` | Enum |
| `BillingIncidentType` | Enum |
| `BillingJobRun` | Entity |
| `BillingIncident` | Entity |
| `BillingContext` | DTO |
| `BillingCalculator` | Service |
| `RecurringBillingService` | Service |
| `GenerateMonthlyInvoicesJob` | Job |
| `GenerateInvoicesCommand` | Console |
| `InvoiceGenerationStarted` | Event |
| `InvoiceGenerated` | Event |
| `InvoiceGenerationSkipped` | Event |
| `InvoiceGenerationFailed` | Event |
| `InvoiceBatchCompleted` | Event |
| `InvoiceDelivered` | Event |
| `SendInvoiceNotification` | Listener |
| `UpdateDebtAging` | Listener |
| `SchedulePaymentReminders` | Listener |
| `LogBillingCompletion` | Listener |
| `InvoiceController` | Controller |
| `BillingController` | Controller |

### Archivos a Modificar (~5)

| Archivo | Cambio |
|---|---|
| `Invoice.php` | Agregar fillable, casts, relaciones, scopes |
| `InvoiceItem.php` | Agregar fillable, casts |
| `InvoiceService.php` | Agregar `generateRecurringInvoice()` |
| `Subscription.php` | Agregar relación `invoices()`, scope `billableToday()` |
| `EventServiceProvider.php` (Finance) | Registrar listeners |
| `FinanceServiceProvider.php` | Registrar command y schedule |
| `config/config.php` (Finance) | Agregar sección billing |

---

## Criterios de Aceptación

El proceso de facturación recurrente está completo cuando:

1. El job se ejecuta diariamente a las 00:01 sin intervención manual
2. Solo se facturan suscripciones activas cuyo `billing_day` coincide con el día de ejecución
3. No se generan facturas duplicadas para el mismo `subscription_id + billing_period + type`
4. Los montos se calculan desde `monthly_price` de la suscripción (congelado), no del catálogo
5. Los descuentos se aplican y decrementan automáticamente
6. Los addons activos se incluyen como items separados
7. Cada factura almacena el `calculation_snapshot` completo
8. Los fallos por suscripción individual no detienen el lote
9. Cada fallo o exclusión queda registrado en `billing_incidents` con causa estructurada
10. Cada corrida queda registrada en `billing_job_runs` con contadores finales
11. El comando `finance:generate-invoices` permite ejecución manual y facturación individual
12. Los eventos `InvoiceGenerated` disparan notificación y actualización de aging
13. La integración fiscal puede fallar sin perder la factura (estado `pending_tax_submission`)
14. Tests cubren idempotencia, descuentos, exclusiones, fallos parciales y mes corto
