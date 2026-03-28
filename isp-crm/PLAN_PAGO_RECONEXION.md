# Plan de Implementación: Proceso de Pago y Reconexión

## Estado Actual del Proyecto

### Ya Implementado

| Componente | Ubicación | Estado |
|---|---|---|
| `Invoice` modelo con `isPaid()` | `Modules/Finance/app/Entities/Invoice.php` | Básico |
| `InvoiceService::markAsPaid()` | `Modules/Finance/app/Services/InvoiceService.php` | Solo marca sin conciliación |
| `Wallet` modelo | `Modules/Finance/app/Entities/Wallet.php` | Solo balance y credit_limit |
| `WalletService::createForCustomer()` | `Modules/Finance/app/Services/WalletService.php` | Solo creación |
| `InvoicePaymentReceived` evento | `Modules/Finance/app/Events/InvoicePaymentReceived.php` | Completo |
| `SubscriptionService::reactivate()` | `Modules/Subscription/app/Services/SubscriptionService.php` | Funcional |
| `ReactivateNetworkService` listener | `Modules/Subscription/app/Listeners/ReactivateNetworkService.php` | Escucha `SubscriptionReactivated` |
| `NetworkProvisioningService::reactivateService()` | `Modules/Network/app/Services/NetworkProvisioningService.php` | Remueve de lista MOROSOS |
| `SubscriptionReactivated` evento | `Modules/Subscription/app/Events/SubscriptionReactivated.php` | Completo |
| API `POST /subscriptions/{id}/reactivate` | `Modules/Subscription/routes/api.php` | Funcional |

### No Implementado

1. Modelo `Payment` — no existe tabla ni entidad de pagos
2. Modelo `PaymentAllocation` — conciliación pago↔factura
3. `WalletTransaction` — historial de movimientos de wallet
4. Webhook de pasarela de pagos
5. Conciliación automática (total, parcial, excedente)
6. Reactivación automática post-pago
7. Pagos sin referencia → bandeja de conciliación manual
8. Protección contra duplicados (idempotency key)
9. Refunds y chargebacks

---

## Fase 1: Migraciones

### 1.1 Tabla `payments`

**Archivo**: `Modules/Finance/database/migrations/2026_03_28_150000_create_payments_table.php`

```
payments
├── id
├── uuid unique
├── customer_id FK → customers
├── amount decimal(10,2)
├── currency varchar(3) default 'PEN'
├── method varchar(30)                  — cash, bank_transfer, credit_card, debit_card, yape, plin, wallet
├── channel varchar(30)                 — office, bank, gateway, webhook, manual
├── status varchar(20)                  — pending, validated, completed, failed, reversed, refunded
├── reference varchar(100) nullable     — número de transacción externo
├── external_id varchar(100) nullable   — ID de la pasarela
├── idempotency_key varchar(100) unique nullable — para prevenir duplicados
├── gateway_response json nullable      — respuesta completa del gateway
├── received_at timestamp               — cuándo se recibió el pago
├── validated_at timestamp nullable     — cuándo fue validado
├── reconciliation_status varchar(20)   — pending, allocated, partially_allocated, unmatched
├── notes text nullable
├── processed_by FK nullable → users    — cajero o usuario que registró
├── metadata json nullable
├── timestamps
├── softDeletes

INDEX: customer_id + status
INDEX: reference
INDEX: external_id
INDEX: idempotency_key (unique)
INDEX: reconciliation_status
INDEX: received_at
```

### 1.2 Tabla `payment_allocations`

**Archivo**: `Modules/Finance/database/migrations/2026_03_28_150100_create_payment_allocations_table.php`

```
payment_allocations
├── id
├── payment_id FK → payments
├── invoice_id FK → invoices
├── amount decimal(10,2)               — monto asignado a esta factura
├── allocated_at timestamp
├── allocated_by varchar(20)            — system, manual
├── notes text nullable
├── timestamps

INDEX: payment_id
INDEX: invoice_id
UNIQUE: payment_id + invoice_id
```

### 1.3 Tabla `wallet_transactions`

**Archivo**: `Modules/Finance/database/migrations/2026_03_28_150200_create_wallet_transactions_table.php`

```
wallet_transactions
├── id
├── wallet_id FK → wallets
├── type varchar(20)                    — credit, debit
├── amount decimal(10,2)
├── balance_after decimal(10,2)
├── concept varchar(50)                 — payment_excess, refund, adjustment, plan_change_credit, initial_balance
├── reference_type varchar(50) nullable — App\Models\Payment, App\Models\Invoice, etc.
├── reference_id bigint nullable
├── description text nullable
├── created_by FK nullable → users
├── timestamps

INDEX: wallet_id + created_at
INDEX: type
INDEX: reference_type + reference_id
```

### 1.4 Tabla `payment_webhook_logs`

**Archivo**: `Modules/Finance/database/migrations/2026_03_28_150300_create_payment_webhook_logs_table.php`

```
payment_webhook_logs
├── id
├── gateway varchar(30)                 — mercadopago, niubiz, izipay, yape
├── event_type varchar(50)
├── external_id varchar(100) nullable
├── payload json
├── signature varchar(255) nullable
├── ip_address varchar(45)
├── status varchar(20)                  — received, processed, rejected, duplicate, failed
├── processing_result text nullable
├── payment_id FK nullable → payments   — si se creó un pago
├── processed_at timestamp nullable
├── timestamps

INDEX: gateway + external_id
INDEX: status
```

### 1.5 Agregar `total_paid` a `invoices`

**Archivo**: `Modules/Finance/database/migrations/2026_03_28_150400_add_payment_tracking_to_invoices_table.php`

Campos nuevos en `invoices`:
- `total_paid` decimal(10,2) default 0
- `balance_due` decimal(10,2) default 0 — se puede calcular pero útil desnormalizado

---

## Fase 2: Enums

**Archivo**: `Modules/Finance/app/Enums/PaymentMethod.php`
```php
enum PaymentMethod: string
{
    case CASH = 'cash';
    case BANK_TRANSFER = 'bank_transfer';
    case CREDIT_CARD = 'credit_card';
    case DEBIT_CARD = 'debit_card';
    case YAPE = 'yape';
    case PLIN = 'plin';
    case WALLET = 'wallet';
    case CHECK = 'check';
}
```

**Archivo**: `Modules/Finance/app/Enums/PaymentChannel.php`
```php
enum PaymentChannel: string
{
    case OFFICE = 'office';
    case BANK = 'bank';
    case GATEWAY = 'gateway';
    case WEBHOOK = 'webhook';
    case MANUAL = 'manual';
    case WALLET = 'wallet';
}
```

**Archivo**: `Modules/Finance/app/Enums/PaymentStatus.php`
```php
enum PaymentStatus: string
{
    case PENDING = 'pending';
    case VALIDATED = 'validated';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case REVERSED = 'reversed';
    case REFUNDED = 'refunded';
}
```

**Archivo**: `Modules/Finance/app/Enums/ReconciliationStatus.php`
```php
enum ReconciliationStatus: string
{
    case PENDING = 'pending';
    case ALLOCATED = 'allocated';
    case PARTIALLY_ALLOCATED = 'partially_allocated';
    case UNMATCHED = 'unmatched';
}
```

**Archivo**: `Modules/Finance/app/Enums/WalletTransactionType.php`
```php
enum WalletTransactionType: string
{
    case CREDIT = 'credit';
    case DEBIT = 'debit';
}
```

**Archivo**: `Modules/Finance/app/Enums/WalletConcept.php`
```php
enum WalletConcept: string
{
    case PAYMENT_EXCESS = 'payment_excess';
    case REFUND = 'refund';
    case ADJUSTMENT = 'adjustment';
    case PLAN_CHANGE_CREDIT = 'plan_change_credit';
    case INITIAL_BALANCE = 'initial_balance';
    case PAYMENT_FROM_WALLET = 'payment_from_wallet';
}
```

---

## Fase 3: Entidades

### 3.1 Modelos nuevos

**`Modules/Finance/app/Entities/Payment.php`**
- Relaciones: `belongsTo(Customer)`, `hasMany(PaymentAllocation)`, `processedBy → belongsTo(User)`
- Métodos: `isCompleted()`, `getRemainingAmount()` (amount - sum allocations), `isFullyAllocated()`

**`Modules/Finance/app/Entities/PaymentAllocation.php`**
- Relaciones: `belongsTo(Payment)`, `belongsTo(Invoice)`

**`Modules/Finance/app/Entities/WalletTransaction.php`**
- Relaciones: `belongsTo(Wallet)`, morph `reference`

**`Modules/Finance/app/Entities/PaymentWebhookLog.php`**
- Relación: `belongsTo(Payment, nullable)`

### 3.2 Actualizar modelos existentes

**`Invoice`** — agregar:
```php
public function payments(): HasManyThrough (via PaymentAllocation)
public function allocations(): HasMany (PaymentAllocation)
public function recalculateTotals(): void  // total_paid = sum(allocations.amount), balance_due = total - total_paid
```

**`Wallet`** — agregar:
```php
public function transactions(): HasMany (WalletTransaction)
public function credit(float $amount, string $concept, ?string $description, $reference): WalletTransaction
public function debit(float $amount, string $concept, ?string $description, $reference): WalletTransaction
```

**`Customer`** — agregar:
```php
public function payments(): HasMany
```

---

## Fase 4: Servicios

### 4.1 PaymentService

**Archivo**: `Modules/Finance/app/Services/PaymentService.php`

```php
class PaymentService
{
    public function __construct(
        protected PaymentAllocationService $allocationService,
        protected WalletService $walletService,
        protected ReconnectionService $reconnectionService,
    ) {}

    /**
     * Registra un pago desde cualquier canal.
     */
    public function registerPayment(RegisterPaymentDTO $dto): Payment;

    /**
     * Registra pago y concilia contra factura específica.
     */
    public function payInvoice(int $invoiceId, RegisterPaymentDTO $dto): Payment;

    /**
     * Registra pago por webhook de pasarela.
     */
    public function processWebhook(string $gateway, array $payload, string $signature, string $ip): Payment;

    /**
     * Valida un pago pendiente.
     */
    public function validatePayment(Payment $payment): Payment;

    /**
     * Revierte un pago completado.
     */
    public function reversePayment(Payment $payment, string $reason): Payment;
}
```

**Lógica de `registerPayment`**:
```
1. Verificar idempotencia por idempotency_key
2. Si existe pago con mismo key → retornar existente
3. Crear Payment en estado 'completed' (o 'pending' si requiere validación)
4. Si hay invoice_id en el DTO → conciliar
5. Si no hay invoice_id → intentar match automático por reference
6. Si no matchea → dejar como 'unmatched' en bandeja
7. Disparar PaymentReceived
```

### 4.2 PaymentAllocationService

**Archivo**: `Modules/Finance/app/Services/PaymentAllocationService.php`

```php
class PaymentAllocationService
{
    /**
     * Concilia un pago contra una factura específica.
     */
    public function allocateToInvoice(Payment $payment, Invoice $invoice, ?float $amount = null): PaymentAllocation;

    /**
     * Concilia un pago contra las facturas más antiguas del cliente (FIFO).
     */
    public function allocateToOldestInvoices(Payment $payment): array;

    /**
     * Recalcula el estado de la factura según sus allocations.
     */
    public function recalculateInvoiceStatus(Invoice $invoice): void;

    /**
     * Maneja excedente: acredita a wallet.
     */
    public function handleExcess(Payment $payment, float $excess): void;

    /**
     * Obtiene pagos no conciliados para bandeja manual.
     */
    public function getUnmatchedPayments(array $filters = []): LengthAwarePaginator;

    /**
     * Conciliación manual desde bandeja.
     */
    public function manualAllocate(int $paymentId, int $invoiceId, float $amount): PaymentAllocation;
}
```

**Lógica de `allocateToInvoice`**:
```
1. Calcular monto a asignar: min(payment.remainingAmount, invoice.balance_due, $amount)
2. Crear PaymentAllocation
3. Actualizar invoice.total_paid y invoice.balance_due
4. Recalcular estado de factura:
   - total_paid >= total → 'paid' + set paid_at
   - total_paid > 0 && < total → 'partially_paid'
   - total_paid == 0 → sin cambio
5. Si payment está fully allocated → reconciliation_status = 'allocated'
6. Si queda excedente en el pago → handleExcess()
7. Disparar InvoicePaid o InvoicePartiallyPaid
```

### 4.3 ReconnectionService

**Archivo**: `Modules/Finance/app/Services/ReconnectionService.php`

```php
class ReconnectionService
{
    public function __construct(
        protected SubscriptionService $subscriptionService,
    ) {}

    /**
     * Evalúa si una suscripción debe ser reactivada tras un pago.
     */
    public function evaluateReconnection(Invoice $paidInvoice): ?Subscription;

    /**
     * Verifica si la suscripción tiene deuda residual que impida reconexión.
     */
    public function hasBlockingDebt(Subscription $subscription): bool;

    /**
     * Verifica bloqueos no financieros.
     */
    public function hasNonFinancialBlocks(Subscription $subscription): bool;

    /**
     * Ejecuta la reactivación completa.
     */
    public function reconnect(Subscription $subscription, string $reason): Subscription;
}
```

**Lógica de `evaluateReconnection`**:
```
1. Obtener suscripción de la factura
2. Si suscripción NO está suspendida → retornar null
3. Verificar deuda residual: ¿hay otras facturas overdue de la misma suscripción?
4. Si hay deuda residual → no reconectar (retornar null)
5. Verificar bloqueos no financieros (CollectionCase abierto, bloqueo manual)
6. Si todo OK → llamar reconnect()
7. Disparar SubscriptionReactivationRequested
```

**Lógica de `reconnect`**:
```
1. Llamar SubscriptionService::reactivate()
   → Esto dispara SubscriptionReactivated
   → El listener ReactivateNetworkService hace el trabajo de red
2. Registrar resultado
3. Si falla la reconexión técnica → crear incidencia operativa, NO revertir pago
```

### 4.4 Extender WalletService

Agregar a `Modules/Finance/app/Services/WalletService.php`:

```php
public function credit(int $customerId, float $amount, string $concept, ?string $description = null, $reference = null): WalletTransaction;
public function debit(int $customerId, float $amount, string $concept, ?string $description = null, $reference = null): WalletTransaction;
public function getBalance(int $customerId): float;
public function getTransactions(int $customerId, array $filters = []): LengthAwarePaginator;
public function payFromWallet(int $customerId, int $invoiceId): ?Payment;
```

### 4.5 WebhookService

**Archivo**: `Modules/Finance/app/Services/PaymentWebhookService.php`

```php
class PaymentWebhookService
{
    public function __construct(
        protected PaymentService $paymentService,
    ) {}

    public function process(string $gateway, array $payload, string $signature, string $ip): PaymentWebhookLog;
    protected function validateSignature(string $gateway, array $payload, string $signature): bool;
    protected function isDuplicate(string $gateway, string $externalId): bool;
    protected function mapToPaymentDTO(string $gateway, array $payload): RegisterPaymentDTO;
}
```

---

## Fase 5: DTOs

**Archivo**: `Modules/Finance/app/DTOs/RegisterPaymentDTO.php`

```php
final readonly class RegisterPaymentDTO
{
    public function __construct(
        public int $customerId,
        public float $amount,
        public string $method,          // PaymentMethod
        public string $channel,         // PaymentChannel
        public ?int $invoiceId = null,
        public ?string $reference = null,
        public ?string $externalId = null,
        public ?string $idempotencyKey = null,
        public ?array $gatewayResponse = null,
        public ?Carbon $receivedAt = null,
        public ?int $processedBy = null,
        public ?string $notes = null,
    ) {}
}
```

---

## Fase 6: Eventos y Listeners

### 6.1 Eventos

| Evento | Datos |
|---|---|
| `PaymentReceived` | `Payment` |
| `PaymentValidated` | `Payment` |
| `PaymentAllocated` | `Payment`, `PaymentAllocation`, `Invoice` |
| `PaymentReversed` | `Payment`, razón |
| `InvoicePaid` | `Invoice`, `Payment` |
| `InvoicePartiallyPaid` | `Invoice`, `Payment`, balance restante |
| `WalletCredited` | `WalletTransaction`, monto, concepto |
| `WalletDebited` | `WalletTransaction`, monto, concepto |
| `SubscriptionReactivationRequested` | `Subscription`, `Invoice` |
| `ReconnectionCompleted` | `Subscription` |
| `ReconnectionFailed` | `Subscription`, razón |
| `PaymentReconciliationFailed` | `Payment`, razón |
| `WebhookDuplicateDetected` | `PaymentWebhookLog` |

### 6.2 Listeners

| Listener | Escucha | Acción |
|---|---|---|
| `AllocatePaymentToInvoice` | `PaymentReceived` | Conciliar pago si tiene invoice reference |
| `EvaluateReconnection` | `InvoicePaid` | Evaluar si la suscripción debe reactivarse |
| `NotifyPaymentReceived` | `PaymentReceived` | Notificar al cliente |
| `NotifyReconnection` | `ReconnectionCompleted` | Notificar reconexión al cliente |
| `UpdateDunningOnPayment` | `InvoicePaid` | Pausar/cerrar dunning de la factura pagada |
| `FulfillPromiseOnPayment` | `InvoicePaid` | Cumplir promesa de pago si existía |
| `LogWebhookResult` | `PaymentReceived` (si channel=webhook) | Actualizar log del webhook |

### 6.3 EventServiceProvider

```php
protected $listen = [
    PaymentReceived::class => [
        AllocatePaymentToInvoice::class,
        NotifyPaymentReceived::class,
    ],
    InvoicePaid::class => [
        EvaluateReconnection::class,
        UpdateDunningOnPayment::class,
        FulfillPromiseOnPayment::class,
    ],
    ReconnectionCompleted::class => [
        NotifyReconnection::class,
    ],
];
```

---

## Fase 7: Controllers y API

### 7.1 PaymentController

**Archivo**: `Modules/Finance/app/Http/Controllers/PaymentController.php`

| Método | Ruta | Acción |
|---|---|---|
| `POST` | `/api/finance/payments` | Registrar pago manual (cajero) |
| `GET` | `/api/finance/payments` | Listar pagos con filtros |
| `GET` | `/api/finance/payments/{id}` | Detalle del pago |
| `POST` | `/api/finance/payments/{id}/reverse` | Revertir pago |
| `POST` | `/api/finance/invoices/{id}/pay` | Pagar factura directamente |

### 7.2 ReconciliationController

**Archivo**: `Modules/Finance/app/Http/Controllers/ReconciliationController.php`

| Método | Ruta | Acción |
|---|---|---|
| `GET` | `/api/finance/reconciliation/unmatched` | Pagos sin conciliar |
| `POST` | `/api/finance/reconciliation/allocate` | Conciliación manual |
| `POST` | `/api/finance/reconciliation/auto` | Intentar conciliación automática |

### 7.3 WalletController

**Archivo**: `Modules/Finance/app/Http/Controllers/WalletController.php`

| Método | Ruta | Acción |
|---|---|---|
| `GET` | `/api/finance/customers/{id}/wallet` | Balance y detalle |
| `GET` | `/api/finance/customers/{id}/wallet/transactions` | Historial |
| `POST` | `/api/finance/customers/{id}/wallet/credit` | Abonar manualmente |
| `POST` | `/api/finance/customers/{id}/wallet/pay-invoice` | Pagar con saldo |

### 7.4 WebhookController

**Archivo**: `Modules/Finance/app/Http/Controllers/PaymentWebhookController.php`

| Método | Ruta | Acción |
|---|---|---|
| `POST` | `/api/webhooks/payments/{gateway}` | Recibir webhook de pasarela |

Nota: Este endpoint NO debe requerir autenticación JWT; valida por firma.

---

## Fase 8: Configuración

**Agregar a `Modules/Finance/config/config.php`**:

```php
'payments' => [
    'default_currency' => env('PAYMENT_CURRENCY', 'PEN'),
    'auto_allocate' => env('PAYMENT_AUTO_ALLOCATE', true),
    'allocation_strategy' => 'oldest_first',  // oldest_first | specific
    'excess_to_wallet' => env('PAYMENT_EXCESS_TO_WALLET', true),
    'auto_reconnect' => env('PAYMENT_AUTO_RECONNECT', true),
    'reconnect_delay_seconds' => env('PAYMENT_RECONNECT_DELAY', 0),
],
'webhooks' => [
    'gateways' => [
        'mercadopago' => [
            'secret' => env('WEBHOOK_MERCADOPAGO_SECRET'),
            'ip_whitelist' => env('WEBHOOK_MERCADOPAGO_IPS', ''),
        ],
        'niubiz' => [
            'secret' => env('WEBHOOK_NIUBIZ_SECRET'),
            'ip_whitelist' => env('WEBHOOK_NIUBIZ_IPS', ''),
        ],
    ],
    'replay_protection_minutes' => 5,
],
```

---

## Fase 9: Tests

### Tests Unitarios

**`PaymentServiceTest`**:
- Registrar pago exitoso
- Idempotencia: mismo `idempotency_key` retorna pago existente
- Pago con invoice_id concilia automáticamente
- Pago sin referencia queda como `unmatched`

**`PaymentAllocationServiceTest`**:
- Pago total → factura pasa a `paid`
- Pago parcial → factura pasa a `partially_paid`
- Excedente → se acredita a wallet
- FIFO: `allocateToOldestInvoices` respeta orden por `due_date`
- Conciliación manual desde bandeja

**`ReconnectionServiceTest`**:
- Factura pagada de suscripción suspendida → reconecta
- Factura pagada pero hay otra overdue → NO reconecta
- Suscripción no suspendida → no hace nada
- Fallo de reconexión técnica → crea incidencia, no revierte pago

**`WalletServiceTest`**:
- Credit aumenta balance y crea transaction
- Debit reduce balance y crea transaction
- Debit no permite balance negativo (excepto con credit_limit)
- `payFromWallet` crea Payment + Allocation

### Tests de Integración

**`PaymentReconnectionFlowTest`**:
```
1. Crear suscripción activa → facturar → vencer → suspender por mora
2. Registrar pago por la factura
3. Assert: factura en estado 'paid'
4. Assert: suscripción reactivada automáticamente
5. Assert: servicio de red reconectado
6. Assert: wallet sin excedente
```

**`PartialPaymentTest`**:
```
1. Factura de $100 → pago de $60
2. Assert: factura 'partially_paid', balance_due = $40
3. Pago de $50
4. Assert: factura 'paid'
5. Assert: wallet creditada con $10 de excedente
```

**`WebhookTest`**:
```
1. Enviar webhook válido
2. Assert: pago creado y conciliado
3. Enviar mismo webhook de nuevo (replay)
4. Assert: detectado como duplicado, no se crea segundo pago
```

---

## Resumen de Archivos

### Nuevos (~30)

| Tipo | Cantidad |
|---|---|
| Migraciones | 5 |
| Enums | 6 |
| Entidades | 4 |
| Servicios | 5 |
| DTOs | 1 |
| Eventos | 13 |
| Listeners | 7 |
| Controllers | 4 |

### A Modificar (~5)

- `Invoice.php` — agregar total_paid, balance_due, relaciones
- `Wallet.php` — agregar métodos credit/debit, relación transactions
- `WalletService.php` — agregar credit/debit/payFromWallet
- `InvoiceService.php` — agregar markAsPaid mejorado con recalculate
- `config.php` (Finance) — agregar secciones payments y webhooks
- `EventServiceProvider.php` (Finance) — registrar listeners

---

## Criterios de Aceptación

1. Se pueden registrar pagos desde oficina, banco, pasarela y wallet
2. Cada pago se concilia contra facturas automáticamente (FIFO) o manualmente
3. No se crean pagos duplicados (idempotency key para webhooks)
4. Pagos parciales actualizan `total_paid` y `balance_due` correctamente
5. Excedentes se acreditan a la wallet del cliente con trazabilidad
6. La reactivación ocurre automáticamente al pagar toda la deuda de una suscripción suspendida
7. Si la reconexión técnica falla, el pago NO se revierte — se crea incidencia
8. Pagos sin referencia van a bandeja de conciliación manual
9. Webhooks se validan por firma, IP y protección contra replay
10. El historial de wallet registra cada movimiento con concepto y referencia
11. La integración con dunning se cierra: factura pagada → dunning pausado/cerrado
12. Promesas de pago se cumplen automáticamente al detectar el pago
