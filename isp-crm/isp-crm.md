# ISP-CRM NORETEL — Manual Técnico de Arquitectura

> **Versión:** 1.0  
> **Última actualización:** Diciembre 2025  
> **Propósito:** Guía completa para desarrollo con Claude Code

---

## 1. STACK TECNOLÓGICO

### 1.1 Backend

| Componente | Especificación |
|------------|----------------|
| PHP | 8.2+ (strict_types obligatorio) |
| Framework | Laravel 11.x |
| Arquitectura | Monolito Modular (nwidart/laravel-modules) |
| Base de Datos | MySQL 8.0 (InnoDB, Foreign Keys estrictas) |
| Cache/Colas | Redis 7.x |
| API Network | RouterOS API, SNMP para OLTs |

### 1.2 Frontend

| Componente | Especificación |
|------------|----------------|
| Templates | Laravel Blade |
| Estilos | Tailwind CSS 3.x |
| Interactividad | Alpine.js 3.x |
| Componentes UI | Biblioteca Blade personalizada |
| **PROHIBIDO** | React, Vue, jQuery |

### 1.3 Estructura de Directorios

```
noretel-crm/
├── Modules/
│   ├── Core/              # Traits, Interfaces, Base Classes
│   ├── AccessControl/     # Usuarios, Roles, Permisos
│   ├── Workflow/          # Motor de estados
│   ├── Crm/               # Clientes y prospectos
│   ├── Network/           # Infraestructura de red
│   ├── Inventory/         # Almacén y stock
│   ├── Catalog/           # Planes y servicios
│   ├── Subscription/      # Contratos activos
│   ├── FieldOps/          # Órdenes de trabajo
│   └── Finance/           # Facturación
├── app/
│   ├── Providers/
│   └── Exceptions/
├── config/
├── database/
├── resources/
│   └── views/
│       └── components/    # UI Kit Blade
├── routes/
└── tests/
```

---

## 2. ARQUITECTURA DE MÓDULOS

### 2.1 Estructura Interna de Cada Módulo

```
Modules/{NombreModulo}/
├── Config/
│   └── config.php
├── Database/
│   ├── Migrations/
│   ├── Seeders/
│   └── Factories/
├── Entities/              # Modelos Eloquent
├── DTOs/                  # Data Transfer Objects
├── Services/              # Lógica de negocio
├── Repositories/          # Acceso a datos (opcional)
├── Actions/               # Acciones atómicas reutilizables
├── Events/                # Eventos del dominio
├── Listeners/             # Handlers de eventos
├── Policies/              # Autorización
├── Enums/                 # Enumeraciones PHP 8.1+
├── Exceptions/            # Excepciones del módulo
├── Http/
│   ├── Controllers/
│   ├── Requests/          # Form Requests
│   ├── Resources/         # API Resources
│   └── Middleware/
├── Jobs/                  # Trabajos en cola
├── Console/               # Comandos Artisan
├── Routes/
│   ├── web.php
│   └── api.php
├── Providers/
│   └── {Modulo}ServiceProvider.php
├── Resources/
│   └── views/
└── Tests/
    ├── Unit/
    └── Feature/
```

### 2.2 Reglas de Comunicación entre Módulos

```
┌─────────────────────────────────────────────────────────────────┐
│                    REGLAS FUNDAMENTALES                         │
├─────────────────────────────────────────────────────────────────┤
│ ✗ PROHIBIDO: Acceder directamente a Entidades de otro módulo   │
│ ✗ PROHIBIDO: Usar modelos de otro módulo en queries directas   │
│ ✓ PERMITIDO: Llamar Services públicos de otro módulo           │
│ ✓ PERMITIDO: Usar DTOs para transferir datos                   │
│ ✓ PERMITIDO: Escuchar Events de otros módulos                  │
│ ✓ PERMITIDO: Usar Contracts (interfaces) compartidas           │
└─────────────────────────────────────────────────────────────────┘
```

---

## 3. MÓDULOS Y ENTIDADES

### 3.1 Core (Módulo Base)

> Proporciona funcionalidad compartida para todos los módulos.

#### Traits

| Trait | Descripción |
|-------|-------------|
| `HasUuid` | Genera UUID automático al crear registro |
| `HasScope` | Aplica Global Scope basado en permisos del usuario |
| `Auditable` | Registra created_by, updated_by automáticamente |
| `HasStatus` | Manejo estandarizado de estados |
| `Searchable` | Búsqueda full-text configurable |

#### Interfaces (Contracts)

```php
// Contracts/Activatable.php
interface Activatable
{
    public function activate(): void;
    public function deactivate(): void;
    public function isActive(): bool;
}

// Contracts/Provisionable.php
interface Provisionable
{
    public function provision(): ProvisionResult;
    public function deprovision(): ProvisionResult;
    public function getProvisionStatus(): ProvisionStatus;
}
```

#### Base Classes

```php
// Services/BaseService.php
abstract class BaseService
{
    protected function transaction(callable $callback): mixed;
    protected function dispatchEvent(object $event): void;
}

// Repositories/BaseRepository.php
abstract class BaseRepository
{
    public function findOrFail(int $id): Model;
    public function paginate(int $perPage = 15): LengthAwarePaginator;
    public function create(array $data): Model;
    public function update(Model $model, array $data): Model;
}
```

---

### 3.2 AccessControl

> Gestión de identidad, autenticación, roles y permisos granulares.

#### Entidades

| Entidad | Campos Principales | Relaciones |
|---------|-------------------|------------|
| **User** | id, uuid, name, email, password, phone, is_active, zone_id, last_login_at | belongsTo Zone, belongsToMany Roles |
| **Role** | id, code, name, description, is_system | belongsToMany Permissions, belongsToMany Users |
| **Permission** | id, code, name, module, description | belongsToMany Roles |
| **Zone** | id, code, name, parent_id, polygon (JSON) | hasMany Users, hasMany Addresses |
| **UserSession** | id, user_id, ip_address, user_agent, last_activity | belongsTo User |

#### Enums

```php
enum RoleCode: string
{
    case SUPERADMIN = 'superadmin';
    case ADMIN = 'admin';
    case SUPERVISOR = 'supervisor';
    case TECHNICIAN = 'technician';
    case SALES = 'sales';
    case BILLING = 'billing';
    case SUPPORT = 'support';
}
```

#### Permisos Base del Sistema

```php
// Formato: {modulo}.{entidad}.{accion}[.{constraint}]
return [
    // CRM
    'crm.lead.view', 'crm.lead.view.own', 'crm.lead.view.zone',
    'crm.lead.create', 'crm.lead.update', 'crm.lead.delete', 'crm.lead.convert',
    'crm.customer.view', 'crm.customer.view.zone',
    'crm.customer.create', 'crm.customer.update', 'crm.customer.delete',
    
    // Subscription
    'subscription.contract.view', 'subscription.contract.view.zone',
    'subscription.contract.create', 'subscription.contract.update',
    'subscription.contract.cancel', 'subscription.contract.suspend',
    'subscription.contract.override_price', // Solo supervisores+
    
    // FieldOps
    'fieldops.workorder.view', 'fieldops.workorder.view.own', 'fieldops.workorder.view.zone',
    'fieldops.workorder.create', 'fieldops.workorder.assign',
    'fieldops.workorder.start', 'fieldops.workorder.complete',
    'fieldops.workorder.cancel', 'fieldops.workorder.reassign',
    
    // Finance
    'finance.invoice.view', 'finance.invoice.view.zone',
    'finance.invoice.create', 'finance.invoice.void',
    'finance.payment.view', 'finance.payment.register',
    'finance.payment.void', // Solo admin+
    
    // Network
    'network.device.view', 'network.device.create', 'network.device.configure',
    'network.ip.assign', 'network.ip.release',
    
    // Inventory
    'inventory.stock.view', 'inventory.stock.view.own', // own = su bodega móvil
    'inventory.movement.create', 'inventory.movement.approve',
    'inventory.serial.assign', 'inventory.serial.transfer',
];
```

---

### 3.3 Workflow

> Motor de estados para gestionar procesos de negocio de forma declarativa.

#### Entidades

| Entidad | Campos Principales | Descripción |
|---------|-------------------|-------------|
| **WorkflowDefinition** | id, code, name, description, entity_type, is_active | Define el flujo completo |
| **Place** | id, workflow_id, code, name, color, is_initial, is_final, order | Estado posible |
| **Transition** | id, workflow_id, from_place_id, to_place_id, code, name | Regla de cambio |
| **TransitionPermission** | id, transition_id, role_id | Qué roles pueden ejecutar |
| **Token** | id, workflow_id, tokenable_type, tokenable_id, current_place_id | Instancia activa |
| **TransitionLog** | id, token_id, from_place_id, to_place_id, user_id, metadata, created_at | Historial |
| **SideEffect** | id, transition_id, trigger_point, action_class, parameters | Acciones automáticas |

#### Workflows Predefinidos

```php
// 1. INSTALACIÓN NUEVA
'installation' => [
    'places' => [
        'draft' => ['name' => 'Borrador', 'is_initial' => true],
        'pending_schedule' => ['name' => 'Pendiente Agendar'],
        'scheduled' => ['name' => 'Agendado'],
        'assigned' => ['name' => 'Asignado'],
        'in_transit' => ['name' => 'En Tránsito'],
        'on_site' => ['name' => 'En Sitio'],
        'in_progress' => ['name' => 'En Progreso'],
        'pending_validation' => ['name' => 'Pendiente Validación'],
        'completed' => ['name' => 'Completado', 'is_final' => true],
        'cancelled' => ['name' => 'Cancelado', 'is_final' => true],
        'rescheduled' => ['name' => 'Reagendado'],
    ],
    'transitions' => [
        'submit' => ['from' => 'draft', 'to' => 'pending_schedule'],
        'schedule' => ['from' => 'pending_schedule', 'to' => 'scheduled'],
        'assign' => ['from' => 'scheduled', 'to' => 'assigned'],
        'start_transit' => ['from' => 'assigned', 'to' => 'in_transit'],
        'arrive' => ['from' => 'in_transit', 'to' => 'on_site'],
        'start_work' => ['from' => 'on_site', 'to' => 'in_progress'],
        'submit_completion' => ['from' => 'in_progress', 'to' => 'pending_validation'],
        'approve' => ['from' => 'pending_validation', 'to' => 'completed'],
        'reject' => ['from' => 'pending_validation', 'to' => 'in_progress'],
        'cancel' => ['from' => '*', 'to' => 'cancelled'], // Desde cualquier estado
        'reschedule' => ['from' => ['scheduled', 'assigned'], 'to' => 'rescheduled'],
    ]
]

// 2. SOPORTE TÉCNICO
'support_ticket' => [
    'places' => [
        'open' => ['is_initial' => true],
        'assigned' => [],
        'in_progress' => [],
        'waiting_customer' => [],
        'waiting_parts' => [],
        'resolved' => [],
        'closed' => ['is_final' => true],
    ]
]

// 3. BAJA DE SERVICIO
'service_cancellation' => [
    'places' => [
        'requested' => ['is_initial' => true],
        'pending_equipment_return' => [],
        'pending_final_invoice' => [],
        'completed' => ['is_final' => true],
    ]
]
```

#### Servicio del Motor

```php
class WorkflowService
{
    public function startWorkflow(string $workflowCode, Model $entity): Token
    {
        $workflow = WorkflowDefinition::where('code', $workflowCode)->firstOrFail();
        $initialPlace = $workflow->places()->where('is_initial', true)->firstOrFail();
        
        return Token::create([
            'workflow_id' => $workflow->id,
            'tokenable_type' => get_class($entity),
            'tokenable_id' => $entity->id,
            'current_place_id' => $initialPlace->id,
        ]);
    }
    
    public function canTransition(Token $token, string $transitionCode): bool
    {
        $transition = $this->findTransition($token, $transitionCode);
        if (!$transition) return false;
        
        // Verificar permisos del usuario
        $user = auth()->user();
        return $transition->permissions()
            ->whereIn('role_id', $user->roles->pluck('id'))
            ->exists();
    }
    
    public function executeTransition(Token $token, string $transitionCode, array $metadata = []): Token
    {
        if (!$this->canTransition($token, $transitionCode)) {
            throw new UnauthorizedTransitionException($transitionCode);
        }
        
        $transition = $this->findTransition($token, $transitionCode);
        
        return DB::transaction(function () use ($token, $transition, $metadata) {
            $fromPlaceId = $token->current_place_id;
            
            // Ejecutar side effects de SALIDA
            $this->executeSideEffects($transition, 'on_exit', $token);
            
            // Actualizar token
            $token->update(['current_place_id' => $transition->to_place_id]);
            
            // Registrar en log
            TransitionLog::create([
                'token_id' => $token->id,
                'from_place_id' => $fromPlaceId,
                'to_place_id' => $transition->to_place_id,
                'transition_id' => $transition->id,
                'user_id' => auth()->id(),
                'metadata' => $metadata,
            ]);
            
            // Ejecutar side effects de ENTRADA
            $this->executeSideEffects($transition, 'on_enter', $token);
            
            return $token->fresh();
        });
    }
    
    public function getAvailableTransitions(Token $token): Collection
    {
        $user = auth()->user();
        $roleIds = $user->roles->pluck('id');
        
        return Transition::where('workflow_id', $token->workflow_id)
            ->where('from_place_id', $token->current_place_id)
            ->whereHas('permissions', fn($q) => $q->whereIn('role_id', $roleIds))
            ->get();
    }
}
```

---

### 3.4 Crm

> Gestión comercial de prospectos y clientes. NO maneja deudas ni configuración de red.

#### Entidades

| Entidad | Campos Principales |
|---------|-------------------|
| **Lead** | id, uuid, name, document_type, document_number, phone, email, source, status, notes, zone_id, assigned_to, converted_at, created_by |
| **Customer** | id, uuid, lead_id, customer_type (personal/business), document_type, document_number, name, trade_name, phone, email, billing_email, is_active, credit_limit, tax_exempt, created_by |
| **Address** | id, uuid, customer_id, type (service/billing), label, street, number, floor, apartment, reference, district, city, province, postal_code, latitude, longitude, zone_id, is_default |
| **Contact** | id, customer_id, name, relationship, type (phone/email/whatsapp), value, is_primary, receives_notifications |
| **CustomerDocument** | id, customer_id, type (dni/ruc/contract/other), file_path, file_name, verified_at, verified_by, expires_at |
| **CustomerNote** | id, customer_id, user_id, content, is_pinned |

#### Enums

```php
enum LeadSource: string
{
    case WALK_IN = 'walk_in';
    case PHONE = 'phone';
    case WEBSITE = 'website';
    case REFERRAL = 'referral';
    case SOCIAL_MEDIA = 'social_media';
    case CAMPAIGN = 'campaign';
}

enum LeadStatus: string
{
    case NEW = 'new';
    case CONTACTED = 'contacted';
    case QUALIFIED = 'qualified';
    case PROPOSAL_SENT = 'proposal_sent';
    case NEGOTIATING = 'negotiating';
    case WON = 'won';
    case LOST = 'lost';
}

enum DocumentType: string
{
    case DNI = 'dni';
    case RUC = 'ruc';
    case CE = 'ce';        // Carné de extranjería
    case PASSPORT = 'passport';
}

enum CustomerType: string
{
    case PERSONAL = 'personal';
    case BUSINESS = 'business';
}
```

---

### 3.5 Network

> Infraestructura física y lógica de la red.

#### Entidades

| Entidad | Campos Principales |
|---------|-------------------|
| **Node** | id, code, name, type (tower/datacenter/pop), address, latitude, longitude, altitude, status, commissioned_at |
| **Device** | id, node_id, type (router/olt/switch/ap), brand, model, serial_number, ip_address, mac_address, firmware_version, snmp_community, api_port, api_user, api_password_encrypted, status, last_seen_at |
| **DevicePort** | id, device_id, port_number, port_name, type (ethernet/gpon/sfp), speed_mbps, status (active/inactive/damaged), connected_device_id, description |
| **NapBox** | id, node_id, code, name, type (splitter_1x8/splitter_1x16), latitude, longitude, address, total_ports, status, installed_at |
| **NapPort** | id, nap_box_id, port_number, status (free/occupied/reserved/damaged), subscription_id, label |
| **FiberRoute** | id, from_node_id, to_node_id, distance_meters, fiber_count, route_geojson, status |
| **IpPool** | id, name, network_cidr, gateway, dns_primary, dns_secondary, type (public/private/cgnat), vlan_id, device_id, is_active |
| **IpAddress** | id, pool_id, address, status (free/assigned/reserved/blacklisted), subscription_id, assigned_at, notes |

#### Enums

```php
enum DeviceType: string
{
    case ROUTER = 'router';      // Mikrotik, etc.
    case OLT = 'olt';           // Huawei, ZTE, etc.
    case SWITCH = 'switch';
    case AP = 'ap';             // Access Point
    case ONT = 'ont';           // En cliente
}

enum DeviceStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case MAINTENANCE = 'maintenance';
    case DECOMMISSIONED = 'decommissioned';
}

enum IpStatus: string
{
    case FREE = 'free';
    case ASSIGNED = 'assigned';
    case RESERVED = 'reserved';
    case BLACKLISTED = 'blacklisted';
}
```

#### Servicio de Aprovisionamiento

```php
class NetworkProvisioningService
{
    public function provisionService(Subscription $subscription): ProvisionResult
    {
        return DB::transaction(function () use ($subscription) {
            // 1. Asignar IP
            $ip = $this->ipService->assignFreeIp(
                $subscription->plan->ip_pool_id,
                $subscription->id
            );
            
            // 2. Asignar puerto NAP (si aplica)
            $napPort = $this->napService->assignPort(
                $subscription->address->nearest_nap_id,
                $subscription->id
            );
            
            // 3. Configurar en dispositivo de red
            $device = $subscription->plan->device;
            
            if ($device->type === DeviceType::ROUTER) {
                $this->routerOsService->createPppoeUser($device, [
                    'name' => $subscription->serviceInstance->pppoe_user,
                    'password' => $subscription->serviceInstance->pppoe_password,
                    'profile' => $subscription->plan->router_profile,
                    'remote_address' => $ip->address,
                ]);
            }
            
            if ($device->type === DeviceType::OLT) {
                $this->oltService->authorizeOnu($device, [
                    'serial' => $subscription->serviceInstance->onu_serial,
                    'profile' => $subscription->plan->olt_profile,
                    'vlan' => $subscription->plan->vlan_id,
                ]);
            }
            
            // 4. Actualizar ServiceInstance
            $subscription->serviceInstance->update([
                'ip_address_id' => $ip->id,
                'nap_port_id' => $napPort?->id,
                'provisioned_at' => now(),
                'provision_status' => 'active',
            ]);
            
            return new ProvisionResult(success: true, ip: $ip, napPort: $napPort);
        });
    }
    
    public function suspendService(Subscription $subscription): void
    {
        $device = $subscription->plan->device;
        
        if ($device->type === DeviceType::ROUTER) {
            $this->routerOsService->disablePppoeUser(
                $device,
                $subscription->serviceInstance->pppoe_user
            );
            // O agregar a address-list de morosos
            $this->routerOsService->addToAddressList(
                $device,
                'MOROSOS',
                $subscription->serviceInstance->ipAddress->address
            );
        }
        
        $subscription->serviceInstance->update(['provision_status' => 'suspended']);
    }
    
    public function reactivateService(Subscription $subscription): void
    {
        $device = $subscription->plan->device;
        
        if ($device->type === DeviceType::ROUTER) {
            $this->routerOsService->enablePppoeUser(
                $device,
                $subscription->serviceInstance->pppoe_user
            );
            $this->routerOsService->removeFromAddressList(
                $device,
                'MOROSOS',
                $subscription->serviceInstance->ipAddress->address
            );
        }
        
        $subscription->serviceInstance->update(['provision_status' => 'active']);
    }
}
```

---

### 3.6 Inventory

> Control de stock, trazabilidad de equipos y materiales.

#### Entidades

| Entidad | Campos Principales |
|---------|-------------------|
| **Product** | id, sku, name, description, category_id, unit_of_measure, min_stock, requires_serial, unit_cost, is_active |
| **ProductCategory** | id, name, parent_id, code |
| **Warehouse** | id, code, name, type (central/branch/mobile), address, user_id (si mobile), is_active |
| **Stock** | id, product_id, warehouse_id, quantity, reserved_quantity |
| **Movement** | id, type, product_id, quantity, from_warehouse_id, to_warehouse_id, serial_id, reference_type, reference_id, unit_cost, notes, user_id, approved_by, approved_at |
| **Serial** | id, product_id, serial_number, mac_address, warehouse_id, status, subscription_id, purchase_date, warranty_until |
| **MovementRequest** | id, type, from_warehouse_id, to_warehouse_id, status, requested_by, approved_by, notes |
| **MovementRequestItem** | id, request_id, product_id, quantity_requested, quantity_approved, serial_id |

#### Enums

```php
enum MovementType: string
{
    case PURCHASE = 'purchase';        // Entrada por compra
    case SALE = 'sale';               // Salida por venta
    case TRANSFER = 'transfer';       // Entre almacenes
    case ADJUSTMENT_IN = 'adj_in';    // Ajuste positivo
    case ADJUSTMENT_OUT = 'adj_out';  // Ajuste negativo
    case INSTALLATION = 'installation'; // Consumo en instalación
    case RETURN = 'return';           // Devolución de cliente
    case DAMAGE = 'damage';           // Baja por daño
}

enum SerialStatus: string
{
    case IN_STOCK = 'in_stock';
    case ASSIGNED = 'assigned';       // En cliente
    case IN_TRANSIT = 'in_transit';
    case DAMAGED = 'damaged';
    case RETURNED = 'returned';
    case LOST = 'lost';
}
```

---

### 3.7 Catalog

> Oferta comercial de planes y servicios.

#### Entidades

| Entidad | Campos Principales |
|---------|-------------------|
| **Plan** | id, code, name, description, technology (fiber/wireless/adsl), download_speed, upload_speed, price, installation_fee, ip_pool_id, device_id, router_profile, olt_profile, burst_enabled, priority, is_active, is_visible |
| **PlanParameter** | id, plan_id, key, value, display_name |
| **Promotion** | id, code, name, description, discount_type (percentage/fixed), discount_value, applies_to (monthly/installation/both), min_months, valid_from, valid_until, max_uses, current_uses, is_active |
| **PlanPromotion** | id, plan_id, promotion_id |
| **Addon** | id, code, name, description, price, is_recurring, is_active |
| **PlanAddon** | id, plan_id, addon_id, is_included |

#### Parámetros Técnicos Comunes

```php
// Claves estandarizadas para PlanParameter
const PLAN_PARAMS = [
    'download_speed_mbps',
    'upload_speed_mbps',
    'burst_download_mbps',
    'burst_upload_mbps',
    'burst_threshold',
    'burst_time',
    'priority_queue',       // 1-8
    'address_list',         // Lista en Mikrotik
    'rate_limit_string',    // Formato Mikrotik: rx/tx
    'vlan_id',
    'connection_limit',     // Conexiones simultáneas
    'fup_gb',              // Fair Use Policy en GB
];
```

---

### 3.8 Subscription

> Corazón del negocio. Une Cliente + Plan + Red.

#### Entidades

| Entidad | Campos Principales |
|---------|-------------------|
| **Subscription** | id, uuid, code, customer_id, plan_id, address_id, status, billing_day, billing_cycle (monthly/bimonthly), start_date, end_date, contracted_months, monthly_price, installation_fee, discount_percentage, discount_months_remaining, promotion_id, notes, created_by |
| **ServiceInstance** | id, subscription_id, pppoe_user, pppoe_password, ip_address_id, serial_id (ONU), nap_port_id, onu_serial, provision_status, provisioned_at, last_connection_at |
| **SubscriptionAddon** | id, subscription_id, addon_id, price, start_date, end_date |
| **SubscriptionStatusHistory** | id, subscription_id, from_status, to_status, reason, user_id, metadata |
| **SubscriptionNote** | id, subscription_id, user_id, content, is_internal |

#### Enums

```php
enum SubscriptionStatus: string
{
    case DRAFT = 'draft';
    case PENDING_INSTALLATION = 'pending_installation';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';           // Por mora
    case SUSPENDED_VOLUNTARY = 'suspended_voluntary'; // Vacaciones
    case CANCELLED = 'cancelled';
    case TERMINATED = 'terminated';         // Finalizado contrato
}
```

#### Servicio Principal

```php
class SubscriptionService extends BaseService
{
    public function create(CreateSubscriptionDTO $dto): Subscription
    {
        return $this->transaction(function () use ($dto) {
            // Crear suscripción
            $subscription = Subscription::create([
                'uuid' => Str::uuid(),
                'code' => $this->generateCode(),
                'customer_id' => $dto->customerId,
                'plan_id' => $dto->planId,
                'address_id' => $dto->addressId,
                'status' => SubscriptionStatus::DRAFT,
                'billing_day' => $dto->billingDay,
                'monthly_price' => $dto->plan->price,
                'installation_fee' => $dto->plan->installation_fee,
                'start_date' => $dto->startDate,
                'created_by' => auth()->id(),
            ]);
            
            // Crear instancia de servicio
            ServiceInstance::create([
                'subscription_id' => $subscription->id,
                'pppoe_user' => $this->generatePppoeUser($subscription),
                'pppoe_password' => Str::random(12),
                'provision_status' => 'pending',
            ]);
            
            // Agregar addons
            foreach ($dto->addons as $addonId) {
                $addon = Addon::find($addonId);
                $subscription->addons()->attach($addonId, ['price' => $addon->price]);
            }
            
            // Aplicar promoción si existe
            if ($dto->promotionId) {
                $this->applyPromotion($subscription, $dto->promotionId);
            }
            
            // Iniciar workflow de instalación
            $this->workflowService->startWorkflow('installation', $subscription);
            
            $this->dispatchEvent(new SubscriptionCreated($subscription));
            
            return $subscription;
        });
    }
    
    public function activate(Subscription $subscription): void
    {
        $this->changeStatus($subscription, SubscriptionStatus::ACTIVE, 'Instalación completada');
        $this->dispatchEvent(new SubscriptionActivated($subscription));
    }
    
    public function suspend(Subscription $subscription, string $reason): void
    {
        $this->changeStatus($subscription, SubscriptionStatus::SUSPENDED, $reason);
        $this->networkService->suspendService($subscription);
        $this->dispatchEvent(new SubscriptionSuspended($subscription, $reason));
    }
    
    public function reactivate(Subscription $subscription, string $reason): void
    {
        $this->changeStatus($subscription, SubscriptionStatus::ACTIVE, $reason);
        $this->networkService->reactivateService($subscription);
        $this->dispatchEvent(new SubscriptionReactivated($subscription));
    }
    
    private function changeStatus(Subscription $subscription, SubscriptionStatus $newStatus, string $reason): void
    {
        $oldStatus = $subscription->status;
        
        SubscriptionStatusHistory::create([
            'subscription_id' => $subscription->id,
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'reason' => $reason,
            'user_id' => auth()->id(),
        ]);
        
        $subscription->update(['status' => $newStatus]);
    }
}
```

---

### 3.9 FieldOps

> Operaciones de campo: instalaciones, reparaciones, mudanzas.

#### Entidades

| Entidad | Campos Principales |
|---------|-------------------|
| **WorkOrder** | id, uuid, code, type, subscription_id, customer_id, address_id, priority, assigned_to, scheduled_date, scheduled_time_slot, started_at, completed_at, notes, created_by |
| **WorkOrderType** | id, code, name, workflow_code, default_duration_minutes, requires_materials, checklist_template_id |
| **Appointment** | id, work_order_id, date, time_slot_start, time_slot_end, confirmed_at, confirmed_by, reminder_sent_at |
| **ChecklistTemplate** | id, work_order_type_id, name, items (JSON) |
| **ChecklistResponse** | id, work_order_id, checklist_template_id, responses (JSON), completed_at, completed_by |
| **WorkOrderPhoto** | id, work_order_id, type (before/during/after), file_path, caption, latitude, longitude, taken_at |
| **MaterialUsage** | id, work_order_id, product_id, serial_id, quantity, warehouse_id, notes |
| **TechnicianLocation** | id, user_id, latitude, longitude, accuracy, recorded_at |

#### Enums

```php
enum WorkOrderType: string
{
    case INSTALLATION = 'installation';
    case REPAIR = 'repair';
    case RELOCATION = 'relocation';
    case UPGRADE = 'upgrade';
    case DOWNGRADE = 'downgrade';
    case EQUIPMENT_CHANGE = 'equipment_change';
    case CANCELLATION = 'cancellation';
    case PREVENTIVE = 'preventive';
}

enum WorkOrderPriority: string
{
    case LOW = 'low';
    case NORMAL = 'normal';
    case HIGH = 'high';
    case URGENT = 'urgent';
}

enum TimeSlot: string
{
    case MORNING = 'morning';      // 08:00 - 12:00
    case AFTERNOON = 'afternoon';  // 12:00 - 18:00
    case EVENING = 'evening';      // 18:00 - 21:00
}
```

#### Ejemplo de Checklist Template

```json
{
  "work_order_type": "installation",
  "items": [
    {
      "id": "1",
      "type": "checkbox",
      "label": "Verificar potencia óptica (-8 a -25 dBm)",
      "required": true
    },
    {
      "id": "2",
      "type": "number",
      "label": "Lectura de potencia óptica (dBm)",
      "required": true,
      "min": -30,
      "max": 0
    },
    {
      "id": "3",
      "type": "photo",
      "label": "Foto de fachada con ONU instalada",
      "required": true
    },
    {
      "id": "4",
      "type": "photo",
      "label": "Foto de conexión en NAP",
      "required": true
    },
    {
      "id": "5",
      "type": "checkbox",
      "label": "Cliente firma conformidad",
      "required": true
    },
    {
      "id": "6",
      "type": "signature",
      "label": "Firma del cliente",
      "required": true
    },
    {
      "id": "7",
      "type": "text",
      "label": "Observaciones",
      "required": false
    }
  ]
}
```

---

### 3.10 Finance

> Facturación, cobranza, billetera virtual y gestión de mora.

#### Entidades

| Entidad | Campos Principales |
|---------|-------------------|
| **Invoice** | id, uuid, number, subscription_id, customer_id, type (monthly/prorated/installation/other), subtotal, tax_amount, total, status, issue_date, due_date, paid_at, voided_at, voided_by, void_reason, notes |
| **InvoiceItem** | id, invoice_id, concept, description, quantity, unit_price, subtotal, tax_rate, tax_amount, total |
| **Payment** | id, uuid, invoice_id, customer_id, amount, method, reference, gateway_transaction_id, gateway_response, status, paid_at, voided_at, voided_by, void_reason, created_by |
| **PaymentMethod** | id, code, name, gateway_class, is_active, settings (JSON) |
| **Wallet** | id, customer_id, balance, last_transaction_at |
| **WalletTransaction** | id, wallet_id, type, amount, balance_after, reference_type, reference_id, description |
| **PromiseToPay** | id, subscription_id, customer_id, promise_date, amount, status, fulfilled_at, created_by, notes |
| **DebtAging** | id, customer_id, current, days_30, days_60, days_90, days_90_plus, total, calculated_at |
| **DunningAction** | id, subscription_id, invoice_id, action_type, scheduled_at, executed_at, result, notes |

#### Enums

```php
enum InvoiceStatus: string
{
    case DRAFT = 'draft';
    case ISSUED = 'issued';
    case SENT = 'sent';
    case PARTIALLY_PAID = 'partially_paid';
    case PAID = 'paid';
    case OVERDUE = 'overdue';
    case VOIDED = 'voided';
}

enum PaymentMethod: string
{
    case CASH = 'cash';
    case BANK_TRANSFER = 'bank_transfer';
    case CREDIT_CARD = 'credit_card';
    case DEBIT_CARD = 'debit_card';
    case YAPE = 'yape';
    case PLIN = 'plin';
    case WALLET = 'wallet';
}

enum WalletTransactionType: string
{
    case CREDIT = 'credit';         // Saldo a favor
    case DEBIT = 'debit';           // Uso de saldo
    case REFUND = 'refund';         // Devolución
    case ADJUSTMENT = 'adjustment'; // Ajuste manual
}

enum DunningActionType: string
{
    case REMINDER_EMAIL = 'reminder_email';
    case REMINDER_SMS = 'reminder_sms';
    case REMINDER_WHATSAPP = 'reminder_whatsapp';
    case SUSPENSION_WARNING = 'suspension_warning';
    case SERVICE_SUSPENSION = 'service_suspension';
    case FINAL_NOTICE = 'final_notice';
}
```

---

## 4. SISTEMA DE PERMISOS GRANULARES

### 4.1 Arquitectura de Tres Niveles

```
┌──────────────────────────────────────────────────────────────┐
│                    NIVEL 1: PERMISSION                       │
│  Define la acción base: crm.customer.view                    │
├──────────────────────────────────────────────────────────────┤
│                    NIVEL 2: CONSTRAINT                       │
│  Añade restricción: crm.customer.view.zone                   │
│  (Solo clientes de zonas asignadas al usuario)               │
├──────────────────────────────────────────────────────────────┤
│                    NIVEL 3: SCOPE                            │
│  Define valores específicos: zones = [1, 2, 5]               │
└──────────────────────────────────────────────────────────────┘
```

### 4.2 Implementación del Trait HasScope

```php
<?php

namespace Modules\Core\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasScope
{
    protected static function bootHasScope(): void
    {
        static::addGlobalScope('user_scope', function (Builder $builder) {
            $user = auth()->user();
            
            // Sin usuario o superadmin: sin restricciones
            if (!$user || $user->hasRole('superadmin')) {
                return;
            }
            
            $scopeService = app(ScopeService::class);
            $scopeService->applyScope($builder, $user, static::class);
        });
    }
    
    // Método para deshabilitar scope temporalmente
    public static function withoutUserScope(): Builder
    {
        return static::withoutGlobalScope('user_scope');
    }
}
```

### 4.3 Servicio de Scope

```php
<?php

namespace Modules\Core\Services;

class ScopeService
{
    private array $scopeMap = [
        Customer::class => ['zone_id', 'created_by'],
        Subscription::class => ['customer.zone_id', 'created_by'],
        WorkOrder::class => ['assigned_to', 'customer.zone_id'],
        Invoice::class => ['customer.zone_id'],
    ];
    
    public function applyScope(Builder $builder, User $user, string $modelClass): void
    {
        if (!isset($this->scopeMap[$modelClass])) {
            return;
        }
        
        $permissions = $user->getAllPermissions();
        $modelName = class_basename($modelClass);
        $module = $this->getModuleFromModel($modelClass);
        
        // Buscar constraint específico
        $viewPermission = "{$module}.{$modelName}.view";
        
        if ($user->hasPermission("{$viewPermission}.all")) {
            return; // Sin restricciones
        }
        
        $builder->where(function ($query) use ($user, $viewPermission, $modelClass) {
            // Filtro por zona
            if ($user->hasPermission("{$viewPermission}.zone") && $user->zone_id) {
                $query->orWhere('zone_id', $user->zone_id);
                
                // Incluir zonas hijas si existen
                $childZones = Zone::where('parent_id', $user->zone_id)->pluck('id');
                if ($childZones->isNotEmpty()) {
                    $query->orWhereIn('zone_id', $childZones);
                }
            }
            
            // Filtro por propiedad (created_by o assigned_to)
            if ($user->hasPermission("{$viewPermission}.own")) {
                $ownerField = $this->getOwnerField($modelClass);
                $query->orWhere($ownerField, $user->id);
            }
        });
    }
}
```

### 4.4 Policies

```php
<?php

namespace Modules\FieldOps\Policies;

class WorkOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'fieldops.workorder.view',
            'fieldops.workorder.view.own',
            'fieldops.workorder.view.zone',
        ]);
    }
    
    public function view(User $user, WorkOrder $workOrder): bool
    {
        if ($user->hasPermission('fieldops.workorder.view.all')) {
            return true;
        }
        
        if ($user->hasPermission('fieldops.workorder.view.zone')) {
            return $user->zone_id === $workOrder->customer->address->zone_id;
        }
        
        if ($user->hasPermission('fieldops.workorder.view.own')) {
            return $user->id === $workOrder->assigned_to;
        }
        
        return false;
    }
    
    public function assign(User $user, WorkOrder $workOrder): bool
    {
        return $user->hasPermission('fieldops.workorder.assign')
            && $workOrder->status === 'pending_assignment';
    }
    
    public function complete(User $user, WorkOrder $workOrder): bool
    {
        // Solo el técnico asignado puede completar
        return $user->hasPermission('fieldops.workorder.complete')
            && $user->id === $workOrder->assigned_to
            && $workOrder->status === 'in_progress';
    }
    
    public function overridePrice(User $user, WorkOrder $workOrder): bool
    {
        // Solo supervisores pueden sobrescribir precios
        return $user->hasPermission('subscription.contract.override_price');
    }
}
```

---

## 5. EVENTOS DEL SISTEMA

### 5.1 Catálogo de Eventos

#### Módulo CRM

| Evento | Payload | Listeners |
|--------|---------|-----------|
| `LeadCreated` | Lead $lead | SendWelcomeNotification, AssignToSalesperson |
| `LeadConverted` | Lead $lead, Customer $customer | CreateSubscription, NotifySales |
| `LeadLost` | Lead $lead, string $reason | UpdateStatistics, SendFeedbackRequest |
| `CustomerCreated` | Customer $customer | CreateWallet, SendWelcomeEmail |
| `CustomerUpdated` | Customer $customer, array $changes | SyncWithBilling, AuditLog |

#### Módulo Subscription

| Evento | Payload | Listeners |
|--------|---------|-----------|
| `SubscriptionCreated` | Subscription $sub | StartInstallationWorkflow, NotifyCustomer |
| `SubscriptionActivated` | Subscription $sub | GenerateFirstInvoice, ProvisionNetwork, SendActivationEmail |
| `SubscriptionSuspended` | Subscription $sub, string $reason | SuspendNetworkService, NotifyCustomer, LogAction |
| `SubscriptionReactivated` | Subscription $sub | ReactivateNetworkService, NotifyCustomer |
| `SubscriptionCancelled` | Subscription $sub, string $reason | GenerateFinalInvoice, DeprovisionNetwork, ScheduleEquipmentPickup |
| `SubscriptionPlanChanged` | Subscription $sub, Plan $oldPlan, Plan $newPlan | UpdateNetworkConfig, GenerateProratedInvoice |

#### Módulo FieldOps

| Evento | Payload | Listeners |
|--------|---------|-----------|
| `WorkOrderCreated` | WorkOrder $wo | NotifyTechnician, ReserveMaterials |
| `WorkOrderAssigned` | WorkOrder $wo, User $technician | NotifyTechnician, TransferMaterials |
| `WorkOrderStarted` | WorkOrder $wo | LogTechnicianLocation, NotifyCustomer |
| `WorkOrderCompleted` | WorkOrder $wo | ValidateChecklist, ConfirmMaterialUsage, ActivateSubscription |
| `WorkOrderCancelled` | WorkOrder $wo, string $reason | ReturnReservedMaterials, NotifyCustomer |

#### Módulo Finance

| Evento | Payload | Listeners |
|--------|---------|-----------|
| `InvoiceGenerated` | Invoice $invoice | SendInvoiceEmail, UpdateDebtAging |
| `InvoiceOverdue` | Invoice $invoice | ScheduleDunningActions, UpdateSubscriptionStatus |
| `PaymentReceived` | Payment $payment | ConciliateInvoice, ReactivateIfSuspended, UpdateWallet, SendReceipt |
| `PaymentFailed` | Payment $payment, string $reason | NotifyCustomer, LogFailure |
| `WalletCredited` | Wallet $wallet, float $amount | NotifyCustomer |
| `RefundProcessed` | Payment $originalPayment, float $amount | UpdateInvoice, CreditWallet |

#### Módulo Network

| Evento | Payload | Listeners |
|--------|---------|-----------|
| `DeviceOffline` | Device $device | AlertNetworkTeam, CreateIncident |
| `IpAssigned` | IpAddress $ip, Subscription $sub | LogAssignment |
| `IpReleased` | IpAddress $ip | UpdatePoolStats |
| `ProvisioningCompleted` | Subscription $sub | UpdateServiceInstance, NotifyTechnician |
| `ProvisioningFailed` | Subscription $sub, string $error | AlertSupport, RetryProvisioning |

#### Módulo Inventory

| Evento | Payload | Listeners |
|--------|---------|-----------|
| `StockLow` | Product $product, Warehouse $warehouse | AlertPurchasing, CreatePurchaseRequest |
| `SerialAssigned` | Serial $serial, Subscription $sub | UpdateWarrantyInfo |
| `MaterialConsumed` | WorkOrder $wo, array $materials | UpdateStock, UpdateWorkOrderCost |

### 5.2 Implementación de Eventos

```php
<?php

// Events/SubscriptionActivated.php
namespace Modules\Subscription\Events;

class SubscriptionActivated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public function __construct(
        public readonly Subscription $subscription
    ) {}
    
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("customer.{$this->subscription->customer_id}"),
            new PrivateChannel('admin.subscriptions'),
        ];
    }
}

// Listeners/GenerateFirstInvoice.php
namespace Modules\Finance\Listeners;

class GenerateFirstInvoice
{
    public function __construct(
        private InvoiceService $invoiceService
    ) {}
    
    public function handle(SubscriptionActivated $event): void
    {
        $subscription = $event->subscription;
        
        // Generar factura de instalación + prorrata
        $this->invoiceService->generateActivationInvoice($subscription);
    }
}

// EventServiceProvider (en cada módulo)
protected $listen = [
    SubscriptionActivated::class => [
        GenerateFirstInvoice::class,
        ProvisionNetworkService::class,
        SendActivationEmail::class,
        UpdateDashboardStats::class,
    ],
];
```

---

## 6. PROCESOS DE NEGOCIO DETALLADOS

### 6.1 Alta de Cliente (Onboarding Completo)

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        PROCESO: ALTA DE CLIENTE                         │
└─────────────────────────────────────────────────────────────────────────┘

FASE 1: CAPTACIÓN
─────────────────
1. Vendedor crea Lead con datos básicos
   - Nombre, teléfono, email
   - Fuente (web, referido, campaña)
   - Zona geográfica
   → Event: LeadCreated

2. Sistema asigna Lead a vendedor de la zona
   → Event: LeadAssigned

FASE 2: CALIFICACIÓN
────────────────────
3. Vendedor contacta al prospecto
   - Actualiza estado: contacted → qualified
   - Registra dirección para factibilidad

4. Sistema verifica factibilidad técnica:
   
   NetworkService::checkFeasibility($address)
   {
       // Buscar NAPs cercanas con puertos libres
       $nearestNap = NapBox::nearby($lat, $lng, 500) // 500m radio
           ->whereHas('ports', fn($q) => $q->where('status', 'free'))
           ->orderByDistance($lat, $lng)
           ->first();
       
       if (!$nearestNap) {
           return FeasibilityResult::notFeasible('No hay cobertura');
       }
       
       return FeasibilityResult::feasible($nearestNap);
   }

FASE 3: CONTRATACIÓN
────────────────────
5. Lead acepta → Convertir a Customer
   - Validar documentos (DNI/RUC)
   - Crear Customer + Address + Wallet
   → Event: LeadConverted

6. Crear Subscription en estado 'draft'
   - Seleccionar Plan
   - Aplicar promoción si existe
   - Definir día de facturación
   → Event: SubscriptionCreated

7. Iniciar Workflow 'installation'
   - Token creado en estado 'pending_schedule'

FASE 4: AGENDAMIENTO
────────────────────
8. Coordinador agenda cita con cliente
   - Crear Appointment
   - Transición: pending_schedule → scheduled
   
9. Coordinador asigna técnico
   - Verificar disponibilidad
   - Transición: scheduled → assigned
   → Event: WorkOrderAssigned

10. Transferir materiales a bodega móvil del técnico
    - Crear MovementRequest
    - Aprobar y ejecutar transferencia
    → Event: MaterialsTransferred

FASE 5: INSTALACIÓN
───────────────────
11. Técnico inicia ruta
    - Transición: assigned → in_transit
    - Registrar ubicación GPS

12. Técnico llega al sitio
    - Validar geofence (dentro de 100m de dirección)
    - Transición: in_transit → on_site
    - Registrar hora de llegada
    → Event: TechnicianArrived

13. Técnico ejecuta instalación
    - Transición: on_site → in_progress
    - Escanear MAC/Serial de ONU
    - Sistema valida que equipo está en su bodega
    - Sistema aprovisiona en red:
      * Asignar IP de pool
      * Crear usuario PPPoE o autorizar ONU
    → Event: ProvisioningCompleted

14. Técnico completa checklist
    - Medir potencia óptica
    - Tomar fotos (fachada, ONU, NAP)
    - Firma del cliente
    - Transición: in_progress → pending_validation

FASE 6: VALIDACIÓN Y ACTIVACIÓN
───────────────────────────────
15. Supervisor valida trabajo
    - Revisar checklist y fotos
    - Si OK: Transición → completed
    - Si NO: Transición → in_progress (con observaciones)

16. WorkOrder completada dispara:
    - Confirmar consumo de materiales
    - Activar Subscription → status = 'active'
    → Event: SubscriptionActivated

17. SubscriptionActivated dispara:
    - Generar Invoice (instalación + prorrata)
    - Enviar email de bienvenida
    - Actualizar métricas dashboard
```

#### 6.1.A Especificación recomendada que debe prevalecer para implementación

> La sección anterior describe el flujo base, pero no es suficiente como especificación ejecutable. Esta versión ampliada debe tomarse como la definición oficial del proceso de alta de cliente.

#### Objetivo

Garantizar que una venta solo avance a instalación y activación cuando existan validaciones comerciales, técnicas, documentarias y operativas completas.

#### Resultado esperado

Al finalizar el proceso deben existir, de forma consistente:

- `Lead` cerrado como ganado o convertido
- `Customer` creado y sin duplicados documentarios
- `Address` normalizada y georreferenciada
- `Subscription` en estado `active`
- `ServiceInstance` aprovisionada y operativa
- `WorkOrder` completada con evidencia
- inventario consumido y trazable por serial o lote
- factura inicial emitida según política comercial
- bitácora completa de eventos, responsables y timestamps

#### Actores involucrados

| Actor | Responsabilidad |
|-------|-----------------|
| Ventas | Captación, calificación, oferta y cierre |
| Coordinación | Agenda, asignación y control operativo |
| Técnico | Instalación, evidencias, consumo de materiales |
| Supervisor | Validación final y control de calidad |
| Sistema | Validaciones, reservas, workflow, eventos, trazabilidad |

#### Módulos involucrados

`Crm`, `Network`, `Catalog`, `Subscription`, `Workflow`, `FieldOps`, `Inventory`, `Finance`, `AccessControl`

#### Reglas de negocio obligatorias

1. No se puede convertir un `Lead` a `Customer` si existe otro cliente activo con el mismo `document_type + document_number`, salvo autorización explícita y trazada.
2. La dirección de servicio debe quedar georreferenciada antes de solicitar factibilidad o agendar instalación.
3. La factibilidad debe devolver resultado estructurado: `feasible`, `feasible_with_conditions`, `not_feasible`.
4. La factibilidad aprobada debe reservar capacidad técnica por una ventana limitada, por ejemplo `24 horas`, para evitar sobreventa.
5. No se debe crear una `Subscription` si falta documentación mínima, aceptación contractual o dirección válida.
6. El precio, promoción, instalación y condiciones comerciales deben congelarse en la suscripción al momento del cierre; no deben depender de lecturas futuras del catálogo.
7. La activación comercial no debe ocurrir hasta que la instalación esté validada y el aprovisionamiento de red sea exitoso.
8. El inventario serializado debe escanearse y validarse antes de asociarlo al cliente.
9. Toda excepción operativa debe cerrar con causal estructurada, no con texto libre únicamente.
10. Cada transición relevante debe generar evento de dominio, registro de auditoría y usuario responsable.

#### Datos mínimos por etapa

| Etapa | Datos obligatorios |
|-------|--------------------|
| Lead | nombre, teléfono, fuente, zona, canal, responsable |
| Calificación | dirección, coordenadas, tipo de inmueble, disponibilidad horaria |
| Contratación | documento, titular, plan, precio pactado, día de facturación, términos aceptados |
| Agendamiento | franja horaria, técnico asignado, materiales reservados |
| Instalación | serial/MAC ONU, potencia, fotos, firma o aceptación |
| Activación | resultado de aprovisionamiento, fecha real de inicio, factura inicial |

#### Flujo principal recomendado

**Fase 1: Captación y deduplicación**

1. Ventas crea `Lead` con datos mínimos.
   - nombre o razón social
   - teléfono principal
   - canal de origen
   - zona comercial
   - responsable comercial
   → Event: `LeadCreated`

2. Sistema ejecuta deduplicación temprana.
   - buscar coincidencias por documento, teléfono, email y dirección aproximada
   - si detecta coincidencia, marcar `possible_duplicate`
   - no bloquear automáticamente; enviar a revisión comercial
   → Event: `LeadDuplicateDetected`

3. Sistema asigna el lead según zona, carga de trabajo o regla de negocio.
   → Event: `LeadAssigned`

**Fase 2: Calificación comercial y técnica**

4. Ventas contacta al prospecto y completa perfil comercial.
   - tipo de cliente
   - necesidad de servicio
   - preferencia de plan
   - ventana de instalación deseada
   - consentimiento de tratamiento de datos si aplica

5. Se registra dirección normalizada y coordenadas.
   - usar catálogo geográfico consistente
   - guardar referencia, fachada y punto GPS
   - marcar calidad de georreferenciación

6. Sistema ejecuta factibilidad técnica con salida estructurada.

   ```php
   final readonly class FeasibilityResult
   {
       public function __construct(
           public string $status, // feasible|feasible_with_conditions|not_feasible
           public ?int $napBoxId = null,
           public ?int $napPortId = null,
           public ?int $distanceMeters = null,
           public array $conditions = [],
           public ?string $reason = null,
           public ?Carbon $reservationExpiresAt = null,
       ) {}
   }
   ```

7. Si la factibilidad es positiva:
   - reservar NAP/puerto o capacidad lógica
   - registrar vigencia de la reserva
   - guardar costo estimado de instalación especial si aplica
   → Event: `FeasibilityConfirmed`

8. Si la factibilidad falla:
   - registrar causal estructurada: sin cobertura, sin puertos, distancia excedida, requiere obra, datos insuficientes
   - permitir cierre como `lost` o espera de expansión
   → Event: `LeadFeasibilityRejected`

**Fase 3: Cierre comercial y contractual**

9. Ventas presenta oferta final.
   - plan base
   - addons
   - promoción aplicada
   - costo de instalación
   - prorrata inicial
   - fecha tentativa de instalación

10. Cliente acepta condiciones.
    - validar identidad y documentos
    - validar unicidad documental
    - registrar aceptación contractual
    - opcional: OTP, firma digital o aceptación trazada

11. Sistema convierte el lead.
    - crear `Customer`
    - crear `Address`
    - crear `Contact` principal
    - crear `Wallet` si la política financiera lo requiere
    → Event: `LeadConverted`

12. Sistema crea `Subscription` en estado `draft`.
    - copiar precio pactado y cargos comerciales
    - definir `billing_day`
    - fijar fecha comprometida de instalación
    - crear snapshot comercial del plan y promoción
    → Event: `SubscriptionCreated`

13. Sistema crea la `WorkOrder` de instalación e inicia workflow `installation`.
    - estado inicial recomendado: `pending_schedule`
    - relacionar suscripción, dirección, capacidad reservada y SLA
    → Event: `InstallationWorkflowStarted`

**Fase 4: Agenda y preparación operativa**

14. Coordinación agenda visita.
    - validar disponibilidad del cliente
    - validar capacidad de cuadrilla
    - validar SLA por zona
    - crear `Appointment`
    → Transition: `pending_schedule -> scheduled`
    → Event: `InstallationScheduled`

15. Coordinación asigna técnico o cuadrilla.
    - considerar zona, skills, carga y stock disponible
    - registrar responsable principal y apoyo
    → Transition: `scheduled -> assigned`
    → Event: `WorkOrderAssigned`

16. Sistema prepara materiales.
    - reservar ONU/router/cable/herrajes
    - transferir a bodega móvil si aplica
    - impedir asignación de serial ya comprometido en otra orden
    → Event: `InstallationMaterialsReserved`

**Fase 5: Ejecución en campo**

17. Técnico inicia desplazamiento.
    - check-in operativo
    - transición `assigned -> in_transit`
    - registrar timestamp y geolocalización
    → Event: `TechnicianDispatched`

18. Técnico llega al sitio.
    - validar geofence contra la dirección
    - registrar hora real de llegada
    → Transition: `in_transit -> on_site`
    → Event: `TechnicianArrived`

19. Técnico inicia instalación.
    - transición `on_site -> in_progress`
    - escanear serial y MAC del equipo
    - validar pertenencia del equipo al stock asignado
    - registrar material consumido real

20. Sistema aprovisiona el servicio.
    - asignar IP o recurso lógico
    - crear credenciales PPPoE o autorizar ONU
    - enlazar equipo instalado con `ServiceInstance`
    - guardar respuesta técnica completa del aprovisionamiento
    → Event: `ProvisioningCompleted` o `ProvisioningFailed`

21. Técnico cierra checklist.
    - potencia óptica
    - prueba de navegación
    - fotos obligatorias
    - firma o conformidad del cliente
    - observaciones estructuradas
    → Transition: `in_progress -> pending_validation`
    → Event: `InstallationSubmittedForValidation`

**Fase 6: Validación, activación y facturación**

22. Supervisor valida la orden.
    - revisar checklist, evidencias, potencia y aprovisionamiento
    - si aprueba: `pending_validation -> completed`
    - si rechaza: `pending_validation -> in_progress` con causal obligatoria
    → Event: `InstallationValidated` o `InstallationRejected`

23. Al completar la orden, el sistema ejecuta en transacción:
    - confirmar consumo de inventario
    - asociar seriales al cliente y a la suscripción
    - activar `Subscription`
    - fijar `start_date` real
    - liberar reservas no consumidas
    → Event: `SubscriptionActivated`

24. `SubscriptionActivated` dispara procesos post-activación:
    - generar factura inicial según política: instalación, prorrata, cargo adelantado o diferido
    - enviar bienvenida y credenciales operativas
    - programar facturación recurrente
    - actualizar métricas comerciales y operativas
    → Event: `InitialInvoiceGenerated`

#### Excepciones obligatorias

| Escenario | Acción requerida |
|----------|------------------|
| Duplicado detectado | cola de revisión, bloqueo de conversión sin resolución |
| Factibilidad condicionada | aprobación supervisora o recotización |
| No hay puertos o materiales | replanificar o cancelar con causal |
| Cliente ausente | marcar visita fallida, costo opcional, reagendar |
| Aprovisionamiento fallido | no activar suscripción, abrir incidencia técnica |
| Validación rechazada | devolver a técnico con observaciones y SLA de corrección |
| Instalación incompleta | estado intermedio, sin facturación de servicio activo |

#### Controles faltantes detectados en la versión original

- Detección de duplicados antes de convertir prospectos.
- Reserva temporal de factibilidad para evitar vender capacidad inexistente.
- Snapshot comercial de precio y promoción para evitar inconsistencias futuras.
- Aceptación contractual y validación documental como condición de avance.
- Causales estructuradas para rechazo, cancelación, no factibilidad y visita fallida.
- Activación dentro de transacción de negocio, no como paso implícito aislado.
- Separación clara entre `instalación terminada`, `instalación validada` y `suscripción activada`.
- Evidencia obligatoria y checklist auditable antes de facturar.
- Reglas de idempotencia para aprovisionamiento y generación de factura inicial.

#### Eventos mínimos del dominio

`LeadCreated`, `LeadDuplicateDetected`, `LeadAssigned`, `FeasibilityConfirmed`, `LeadFeasibilityRejected`, `LeadConverted`, `SubscriptionCreated`, `InstallationWorkflowStarted`, `InstallationScheduled`, `WorkOrderAssigned`, `InstallationMaterialsReserved`, `TechnicianDispatched`, `TechnicianArrived`, `ProvisioningCompleted`, `ProvisioningFailed`, `InstallationSubmittedForValidation`, `InstallationValidated`, `InstallationRejected`, `SubscriptionActivated`, `InitialInvoiceGenerated`

#### KPIs recomendados

- tasa de conversión lead a cliente
- tiempo desde lead hasta instalación completada
- porcentaje de factibilidad positiva por zona
- tasa de instalaciones fallidas o reprogramadas
- tiempo promedio de activación post-instalación
- porcentaje de activaciones con retrabajo
- margen por instalación considerando materiales y visita

#### Criterios de cierre

El proceso de alta se considera cerrado solo cuando:

1. la suscripción está `active`
2. la orden de trabajo está `completed`
3. el aprovisionamiento está confirmado
4. el inventario quedó conciliado
5. la factura inicial fue emitida o diferida según política
6. no existen validaciones pendientes ni reservas técnicas abiertas

### 6.2 Facturación Recurrente

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    PROCESO: FACTURACIÓN MENSUAL                         │
└─────────────────────────────────────────────────────────────────────────┘

JOB: GenerateMonthlyInvoices
Frecuencia: Diario a las 00:01

1. Obtener subscripciones a facturar hoy:
   
   Subscription::where('status', 'active')
       ->where('billing_day', now()->day)
       ->whereDoesntHave('invoices', fn($q) => 
           $q->where('type', 'monthly')
             ->whereMonth('issue_date', now()->month)
       )
       ->chunk(100, fn($subs) => $this->processChunk($subs));

2. Para cada Subscription:
   
   a) Calcular monto:
      - Base: $subscription->monthly_price
      - Descuento: Si discount_months_remaining > 0
      - Addons: Sumar addons activos
      - Impuestos: Según configuración fiscal
   
   b) Crear Invoice:
      Invoice::create([
          'subscription_id' => $sub->id,
          'customer_id' => $sub->customer_id,
          'type' => 'monthly',
          'subtotal' => $subtotal,
          'tax_amount' => $tax,
          'total' => $total,
          'status' => 'issued',
          'issue_date' => now(),
          'due_date' => now()->addDays($gracePeriod),
      ]);
   
   c) Crear InvoiceItems:
      - Servicio mensual
      - Cada addon
      - Descuentos (como item negativo)
   
   d) Event: InvoiceGenerated

3. Listeners de InvoiceGenerated:
   - SendInvoiceEmail
   - SendInvoiceSms (si configurado)
   - UpdateDebtAging
   - SchedulePaymentReminders
```
#### 6.2.A Especificación recomendada que debe prevalecer para implementación

> La facturación recurrente no debe verse solo como un job que crea facturas. Debe ser un proceso financiero idempotente, auditable, parametrizable y compatible con impuestos, promociones, prorratas, notas de crédito y reintentos seguros.

#### Objetivo

Emitir cargos periódicos correctos y trazables para cada suscripción facturable, sin duplicidades y respetando reglas comerciales, tributarias y operativas.

#### Resultado esperado

- una sola factura válida por período y por concepto recurrente
- monto calculado desde snapshot comercial vigente de la suscripción
- impuestos, descuentos y addons correctamente desglosados
- recordatorios y acciones posteriores programadas
- trazabilidad de cálculo, emisión, envío y posibles fallos

#### Reglas de negocio obligatorias

1. La facturación recurrente debe basarse en la suscripción y sus condiciones congeladas, no en valores vivos del catálogo.
2. Debe existir una política explícita para facturación adelantada, vencida o al corte.
3. No se debe generar una segunda factura del mismo período si ya existe una factura vigente o anulada que requiera tratamiento especial.
4. Los cálculos deben ser idempotentes por `subscription_id + billing_period + invoice_type`.
5. Los cambios de plan, suspensión, reactivación y cancelación deben afectar la facturación según fecha efectiva, no según fecha de registro.
6. Toda factura debe almacenar su período facturado: `period_start`, `period_end`.
7. La emisión debe soportar reintentos sin duplicar items ni correlativos.
8. Si la integración fiscal falla, la factura debe quedar en estado intermedio controlado y no perderse.

#### Datos y estructuras que faltan

Campos recomendados en `Invoice`:

- `billing_period`
- `period_start`
- `period_end`
- `calculation_snapshot` (JSON)
- `generation_source` (`scheduled`, `manual`, `adjustment`, `migration`)
- `external_tax_status`
- `issued_by_job_run_id`

Campos recomendados en `InvoiceItem`:

- `code`
- `type` (`service`, `addon`, `discount`, `tax`, `proration`, `adjustment`)
- `billing_period_start`
- `billing_period_end`
- `source_reference`

#### Flujo principal recomendado

1. El job `GenerateMonthlyInvoices` crea una ejecución identificable (`job_run_id`).
2. Obtiene suscripciones elegibles:
   - `status` facturable
   - fecha efectiva dentro del período
   - no canceladas antes del período
   - sin factura previa válida del mismo ciclo
3. Para cada suscripción, el sistema arma un `BillingContext`.
   - plan pactado
   - addons activos
   - descuentos vigentes
   - impuestos aplicables
   - política de cobro
   - período a facturar
4. Calcula el cargo:
   - mensualidad base
   - prorrata si la fecha efectiva lo exige
   - descuentos promocionales
   - addons recurrentes
   - ajustes manuales aprobados
   - impuestos
5. Valida reglas previas a emitir:
   - no duplicidad del período
   - customer y subscription vigentes
   - datos fiscales mínimos completos
6. Crea factura y detalle en transacción.
7. Si existe integración tributaria, envía comprobante y actualiza estado.
8. Dispara `InvoiceGenerated`.
9. Los listeners programan recordatorios, actualizan aging y notifican por canales configurados.

#### Excepciones obligatorias

| Escenario | Acción requerida |
|----------|------------------|
| Suscripción suspendida sin cobro permitido | omitir y registrar motivo |
| Suscripción activa con datos fiscales incompletos | generar incidencia operativa y cola de revisión |
| Duplicidad detectada | bloquear emisión y registrar alerta |
| Error tributario externo | estado `pending_tax_submission` o equivalente |
| Error parcial en lote | continuar lote y registrar fallo por suscripción |
| Ajuste manual no aprobado | excluir del cálculo |

#### Controles faltantes detectados en la versión original

- Período facturado explícito.
- Idempotencia por período.
- Snapshot de cálculo guardado en JSON.
- Tratamiento de integración fiscal fallida.
- Criterios claros de elegibilidad y exclusión.
- Manejo de prorratas y ajustes como conceptos tipificados.

#### Eventos mínimos del dominio

`InvoiceGenerationStarted`, `InvoiceGenerated`, `InvoiceGenerationSkipped`, `InvoiceGenerationFailed`, `InvoiceDelivered`, `InvoiceTaxSubmissionFailed`

#### KPIs recomendados

- porcentaje de emisión exitosa por corrida
- facturas omitidas por inconsistencia de datos
- tiempo promedio de emisión por lote
- tasa de fallos de integración fiscal
- facturación emitida versus esperada por ciclo

#### Criterios de cierre

El proceso de facturación mensual se considera correcto cuando cada suscripción elegible quedó en uno de estos estados finales controlados:

1. factura emitida correctamente
2. factura pendiente de integración fiscal con trazabilidad
3. factura omitida con motivo estructurado

### 6.3 Gestión de Mora (Dunning)

```
┌─────────────────────────────────────────────────────────────────────────┐
│                      PROCESO: GESTIÓN DE MORA                           │
└─────────────────────────────────────────────────────────────────────────┘

CONFIGURACIÓN DUNNING (Ejemplo):
────────────────────────────────
días_vencimiento: 0   → Recordatorio email/SMS
días_vencimiento: 3   → Segundo recordatorio + WhatsApp
días_vencimiento: 7   → Aviso de corte inminente
días_vencimiento: 10  → CORTE AUTOMÁTICO
días_vencimiento: 30  → Aviso final antes de cancelación
días_vencimiento: 60  → Cancelación + envío a cobranza

JOB: ProcessDunningActions
Frecuencia: Diario a las 08:00

1. Obtener facturas vencidas:
   
   Invoice::where('status', 'issued')
       ->where('due_date', '<', now())
       ->with(['subscription', 'customer'])
       ->get()
       ->each(fn($invoice) => $this->processDunning($invoice));

2. Para cada factura vencida:
   
   $daysOverdue = now()->diffInDays($invoice->due_date);
   
   // Obtener siguiente acción según días de mora
   $action = DunningConfig::where('min_days', '<=', $daysOverdue)
       ->where('max_days', '>=', $daysOverdue)
       ->first();
   
   // Verificar si ya se ejecutó esta acción
   $alreadyExecuted = DunningAction::where('invoice_id', $invoice->id)
       ->where('action_type', $action->type)
       ->exists();
   
   if (!$alreadyExecuted) {
       $this->executeDunningAction($invoice, $action);
   }

3. Acciones según tipo:

   REMINDER_EMAIL/SMS/WHATSAPP:
   - Enviar notificación
   - Registrar en DunningAction
   
   SUSPENSION_WARNING:
   - Enviar aviso de corte inminente
   - Crear PromiseToPay si cliente lo solicita
   
   SERVICE_SUSPENSION:
   - Verificar que no hay PromiseToPay activa
   - Cambiar Subscription.status → 'suspended'
   - Ejecutar NetworkService::suspendService()
   - Registrar en StatusHistory
   → Event: SubscriptionSuspended

4. PromiseToPay (Excepción al corte):
   
   // Cliente solicita plazo de pago
   PromiseToPay::create([
       'subscription_id' => $sub->id,
       'promise_date' => $requestedDate, // Máx 7 días
       'amount' => $invoice->total,
       'status' => 'pending',
   ]);
   
   // Si cumple antes de promise_date → OK
   // Si no cumple → Corte automático al día siguiente
```
#### 6.3.A Especificación recomendada que debe prevalecer para implementación

> La gestión de mora no es solo una secuencia de recordatorios. Debe ser un motor de cobranza parametrizable, con evidencia, excepciones formales, promesas de pago, reglas de suspensión y protección contra acciones indebidas.

#### Objetivo

Gestionar la cartera vencida de manera automática y controlada, maximizando recuperación y minimizando cortes erróneos o acciones inconsistentes.

#### Resultado esperado

- cada deuda vencida clasificada por antigüedad y riesgo
- acciones de cobranza ejecutadas una sola vez por tramo
- suspensión solo cuando las reglas lo permiten
- promesas de pago controladas y vencibles
- trazabilidad completa de contacto, resultado y estado del servicio

#### Reglas de negocio obligatorias

1. La mora debe calcularse por documento financiero exigible, no solo por estado textual de factura.
2. Las acciones de dunning deben ser configurables por producto, segmento o zona si el negocio lo requiere.
3. No se debe suspender un servicio por una factura si existe disputa abierta, promesa vigente aprobada o acuerdo especial.
4. Las acciones deben ser idempotentes por `invoice_id + action_type + stage`.
5. Debe existir distinción entre `soft collection`, `suspension`, `pre-termination` y `write-off` o envío a cobranza externa.
6. La suspensión debe registrar causal, usuario o job, timestamp y respuesta técnica de red.
7. La reanudación del dunning después de una promesa incumplida debe estar automatizada.

#### Datos y estructuras que faltan

Entidades recomendadas:

- `DunningPolicy`
- `DunningStage`
- `DunningExecution`
- `CollectionCase`
- `PromiseToPayApproval`
- `InvoiceDispute`

Campos recomendados en `PromiseToPay`:

- `approved_by`
- `approved_at`
- `broken_at`
- `max_extension_count`
- `source_channel`

#### Flujo principal recomendado

1. El job `ProcessDunningActions` obtiene documentos vencidos elegibles.
2. Calcula `days_overdue`, bucket de aging y riesgo.
3. Evalúa exclusiones:
   - promesa de pago vigente
   - disputa abierta
   - servicio en corte manual bloqueado
   - cliente corporativo con política diferenciada
4. Determina la etapa de cobranza según política.
5. Ejecuta la acción solo si no fue ejecutada antes para esa etapa.
6. Registra `DunningExecution` con:
   - acción
   - canal
   - resultado
   - payload
   - correlation id
7. Si corresponde suspensión:
   - valida elegibilidad
   - suspende suscripción
   - ejecuta corte técnico
   - deja evidencia del resultado
8. Si la deuda supera umbral final:
   - crear caso de cobranza
   - marcar elegibilidad de cancelación o baja financiera según política

#### Excepciones obligatorias

| Escenario | Acción requerida |
|----------|------------------|
| Promesa de pago vigente | congelar acciones de corte hasta vencimiento |
| Disputa abierta | excluir del motor automático |
| Fallo de suspensión técnica | dejar suscripción en estado de revisión, no asumir corte exitoso |
| Cliente con múltiples servicios | aplicar política por cuenta o por suscripción explícitamente |
| Deuda parcialmente pagada | recalcular aging y etapa antes de actuar |

#### Controles faltantes detectados en la versión original

- Exclusión formal por disputa y acuerdos especiales.
- Separación entre acción de cobranza y resultado técnico de suspensión.
- Historial estructurado de ejecuciones de dunning.
- Política configurable por segmento o servicio.
- Tratamiento explícito de deuda parcial.

#### Eventos mínimos del dominio

`DunningStageTriggered`, `CollectionReminderSent`, `PromiseToPayCreated`, `PromiseToPayBroken`, `SubscriptionSuspensionRequested`, `SubscriptionSuspendedForDebt`, `CollectionCaseOpened`

#### KPIs recomendados

- recuperación por tramo de mora
- porcentaje de suspensiones efectivas
- promesas de pago cumplidas versus incumplidas
- mora por antigüedad
- tiempo promedio de recuperación

#### Criterios de cierre

La gestión de mora sobre un documento termina cuando:

1. la deuda queda pagada
2. existe acuerdo o disputa que la excluye temporalmente
3. se envía a cobranza externa o castigo contable según política

### 6.4 Proceso de Pago y Reconexión

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    PROCESO: PAGO Y RECONEXIÓN                           │
└─────────────────────────────────────────────────────────────────────────┘

FUENTES DE PAGO:
────────────────
1. Manual en oficina (Cajero)
2. Transferencia bancaria (Conciliación manual/automática)
3. Pasarela de pagos (Webhook)
4. Yape/Plin (Webhook o conciliación QR)
5. Wallet del cliente (Saldo a favor)

FLUJO WEBHOOK PASARELA:
───────────────────────

POST /api/webhooks/payment-gateway
{
    "transaction_id": "TRX-123456",
    "reference": "INV-2025-001234",  // Número de factura
    "amount": 89.90,
    "status": "approved",
    "method": "credit_card",
    "timestamp": "2025-12-06T10:30:00Z"
}

1. Validar webhook (firma, IP whitelist)

2. Buscar factura por referencia:
   $invoice = Invoice::where('number', $payload['reference'])->first();

3. Crear Payment:
   $payment = Payment::create([
       'invoice_id' => $invoice->id,
       'customer_id' => $invoice->customer_id,
       'amount' => $payload['amount'],
       'method' => $payload['method'],
       'reference' => $payload['transaction_id'],
       'gateway_response' => $payload,
       'status' => 'completed',
       'paid_at' => $payload['timestamp'],
   ]);

4. Conciliar factura:
   
   $totalPaid = $invoice->payments()->sum('amount');
   
   if ($totalPaid >= $invoice->total) {
       $invoice->update([
           'status' => 'paid',
           'paid_at' => now(),
       ]);
       
       // Si sobra, acreditar a Wallet
       $excess = $totalPaid - $invoice->total;
       if ($excess > 0) {
           WalletService::credit($invoice->customer, $excess, 'Excedente pago');
       }
   } else {
       $invoice->update(['status' => 'partially_paid']);
   }
   
   → Event: PaymentReceived

5. Listener: ReactivateIfSuspended
   
   if ($subscription->status === 'suspended') {
       // Verificar que no tiene otras facturas vencidas
       $otherOverdue = Invoice::where('subscription_id', $subscription->id)
           ->where('status', 'overdue')
           ->where('id', '!=', $invoice->id)
           ->exists();
       
       if (!$otherOverdue) {
           SubscriptionService::reactivate($subscription, 'Pago recibido');
       }
   }

6. SubscriptionReactivated dispara:
   - NetworkService::reactivateService()
   - SendReactivationNotification
```

#### 6.4.A Especificación recomendada que debe prevalecer para implementación

> El proceso de pago y reconexión debe ser tratado como conciliación financiera con efectos operativos, no solo como registro de un `Payment`.

#### Objetivo

Registrar pagos de forma segura, conciliarlos contra documentos pendientes y reactivar servicios suspendidos solo cuando realmente corresponde.

#### Resultado esperado

- pago registrado sin duplicidad
- conciliación exacta contra una o varias facturas
- saldo excedente abonado correctamente
- reactivación automática o manual según reglas
- auditoría de origen del pago y respuesta del canal

#### Reglas de negocio obligatorias

1. Todo pago debe ser idempotente por referencia externa y canal.
2. El sistema debe soportar pago total, parcial, excedente, anticipo y pago sin referencia con conciliación posterior.
3. La conciliación no debe depender solo del número de factura; debe soportar cuenta cliente y reglas de asignación.
4. La reactivación solo procede si no quedan bloqueos financieros u operativos.
5. El estado de la factura debe derivarse de la suma conciliada, no del último pago recibido.
6. Debe existir separación entre `Payment`, `Allocation` y `Reconciliation`.
7. Todo webhook debe validarse por firma, timestamp, replay protection e idempotency key.

#### Datos y estructuras que faltan

Entidades recomendadas:

- `PaymentAllocation`
- `PaymentReconciliation`
- `PaymentWebhookLog`
- `Refund`
- `Chargeback`

Campos recomendados en `Payment`:

- `channel`
- `external_id`
- `idempotency_key`
- `received_amount`
- `currency`
- `exchange_rate`
- `reconciliation_status`

#### Flujo principal recomendado

1. Ingreso del pago por cualquier canal.
2. Validación de autenticidad y duplicidad.
3. Registro inicial del pago en estado `received` o `pending_validation`.
4. Identificación del destino:
   - factura específica
   - cuenta cliente
   - saldo a favor
5. Conciliación mediante `PaymentAllocation`.
6. Recalcular estado de cada factura afectada:
   - `issued`
   - `partially_paid`
   - `paid`
7. Si existe excedente:
   - acreditarlo a `Wallet` o dejarlo como saldo no aplicado según política
8. Si la suscripción estaba suspendida:
   - verificar deuda residual
   - verificar bloqueos no financieros
   - solicitar reactivación
9. Registrar resultado técnico de reconexión y notificar al cliente.

#### Excepciones obligatorias

| Escenario | Acción requerida |
|----------|------------------|
| Webhook repetido | reconocer y no duplicar pago |
| Monto distinto al esperado | conciliar parcial o marcar a revisión |
| Pago sin referencia | llevar a bandeja de conciliación |
| Chargeback o reverso | generar débito compensatorio y reevaluar estado |
| Reconexión técnica fallida | dejar pago conciliado, pero abrir incidencia operativa |

#### Controles faltantes detectados en la versión original

- Idempotencia fuerte de webhooks y referencias.
- Modelo de asignación de pagos a múltiples documentos.
- Soporte formal para saldos sin aplicar y pagos no referenciados.
- Separación entre conciliación financiera y reconexión técnica.
- Manejo de reversos, anulaciones y chargebacks.

#### Eventos mínimos del dominio

`PaymentReceived`, `PaymentValidated`, `PaymentAllocated`, `InvoicePaid`, `InvoicePartiallyPaid`, `WalletCredited`, `SubscriptionReactivationRequested`, `SubscriptionReactivated`, `PaymentReconciliationFailed`

#### KPIs recomendados

- tasa de conciliación automática
- pagos no referenciados pendientes
- tiempo promedio de reconexión tras pago
- porcentaje de reconexiones fallidas
- porcentaje de pagos duplicados detectados

#### Criterios de cierre

El proceso cierra cuando:

1. el pago quedó validado y conciliado
2. el documento afectado quedó con estado correcto
3. el excedente fue aplicado o reservado
4. la reconexión quedó ejecutada o derivada con incidencia formal

### 6.5 Cambio de Plan (Upgrade/Downgrade)

```
┌─────────────────────────────────────────────────────────────────────────┐
│                      PROCESO: CAMBIO DE PLAN                            │
└─────────────────────────────────────────────────────────────────────────┘

1. Cliente solicita cambio de plan

2. Calcular diferencia:
   
   $oldPlan = $subscription->plan;
   $newPlan = Plan::find($newPlanId);
   $daysRemaining = now()->diffInDays($subscription->nextBillingDate());
   
   // Prorrata del plan actual (crédito)
   $creditAmount = ($oldPlan->price / 30) * $daysRemaining;
   
   // Prorrata del nuevo plan (débito)
   $debitAmount = ($newPlan->price / 30) * $daysRemaining;
   
   $difference = $debitAmount - $creditAmount;

3. Si upgrade ($difference > 0):
   - Generar factura por diferencia
   - Aplicar cambio inmediatamente al pagar
   
4. Si downgrade ($difference < 0):
   - Acreditar diferencia a Wallet
   - Aplicar cambio al siguiente ciclo (o inmediato según política)

5. Ejecutar cambio:
   
   DB::transaction(function () use ($subscription, $newPlan, $difference) {
       // Actualizar suscripción
       $subscription->update([
           'plan_id' => $newPlan->id,
           'monthly_price' => $newPlan->price,
       ]);
       
       // Reconfigurar en red
       NetworkService::updatePlanConfig($subscription, $newPlan);
       
       // Registrar cambio
       SubscriptionStatusHistory::create([...]);
   });
   
   → Event: SubscriptionPlanChanged
```
#### 6.5.A Especificación recomendada que debe prevalecer para implementación

> El cambio de plan impacta facturación, aprovisionamiento, experiencia del cliente y márgenes. Debe manejarse como proceso contractual con fecha efectiva, validación técnica y reglas claras para upgrade y downgrade.

#### Objetivo

Modificar el plan de una suscripción sin romper consistencia comercial, técnica ni financiera.

#### Resultado esperado

- cambio registrado con trazabilidad de plan origen y destino
- diferencia económica calculada correctamente
- fecha efectiva clara
- reconfiguración técnica exitosa o rollback controlado
- impacto financiero reflejado en factura, nota de crédito o wallet

#### Reglas de negocio obligatorias

1. Todo cambio de plan debe guardar snapshot del plan anterior y del nuevo.
2. Debe existir fecha efectiva del cambio: inmediata, próxima facturación o programada.
3. Un downgrade no debe aplicarse de inmediato si viola permanencia mínima o política comercial.
4. Un upgrade inmediato requiere validación de factibilidad técnica del nuevo perfil.
5. El cálculo económico debe considerar ciclo restante, impuestos, promociones activas y cargos no recurrentes.
6. El cambio debe ser idempotente por solicitud aprobada.
7. Si la reconfiguración técnica falla, el sistema debe revertir o dejar el caso en estado controlado sin cobrar de más.

#### Datos y estructuras que faltan

Entidades recomendadas:

- `PlanChangeRequest`
- `PlanChangeApproval`
- `PlanChangeExecution`

Campos recomendados:

- `effective_at`
- `effective_mode`
- `billing_adjustment_type`
- `old_plan_snapshot`
- `new_plan_snapshot`
- `rollback_required`

#### Flujo principal recomendado

1. Cliente solicita cambio de plan.
2. Sistema valida elegibilidad:
   - suscripción activa
   - sin bloqueo contractual
   - cobertura técnica para el plan destino
3. Calcula impacto económico:
   - crédito por saldo no consumido del plan actual
   - débito proporcional del plan nuevo
   - impuestos y promociones
4. Determina modalidad:
   - `upgrade_immediate`
   - `downgrade_next_cycle`
   - `scheduled_change`
5. Si requiere cobro adicional:
   - generar documento por diferencia o dejar cargo programado
6. Si genera saldo a favor:
   - emitir nota de crédito o abonar a wallet según política
7. Ejecuta el cambio en transacción controlada:
   - actualizar suscripción
   - registrar snapshots
   - disparar reprovisión de red
   - registrar resultado
8. Confirmar al cliente fecha efectiva y nueva tarifa.

#### Excepciones obligatorias

| Escenario | Acción requerida |
|----------|------------------|
| Plan destino sin factibilidad | rechazar solicitud con causal |
| Cambio durante promoción fija | exigir aprobación o postergar |
| Falla en reprovisión | rollback o estado `pending_network_change` |
| Diferencia negativa con política de no wallet | emitir crédito fiscal o saldo aplicado futuro |
| Solicitudes concurrentes | permitir solo una solicitud abierta |

#### Controles faltantes detectados en la versión original

- Fecha efectiva formal del cambio.
- Validación técnica previa al upgrade.
- Snapshot contractual del plan origen y destino.
- Manejo de concurrencia y solicitudes abiertas.
- Rollback o estado de compensación si falla la reprovisión.
- Distinción entre impacto financiero inmediato y programado.

#### Eventos mínimos del dominio

`PlanChangeRequested`, `PlanChangeApproved`, `PlanChangeRejected`, `PlakjjnChangeBilled`, `PlanChangeExecuted`, `PlanChangeProvisioningFailed`, `SubscriptionPlanChanged`

#### KPIs recomendados

- tasa de upgrades versus downgrades
- tiempo promedio de ejecución del cambio
- porcentaje de cambios con fallo técnico
- ingreso incremental por upgrades
- créditos generados por downgrades

#### Criterios de cierre

El cambio de plan cierra cuando:

1. la solicitud quedó aprobada o rechazada con causal
2. el impacto financiero quedó resuelto
3. la configuración técnica quedó aplicada o compensada
4. la suscripción refleja el nuevo plan con fecha efectiva correcta

---

## 7. JOBS PROGRAMADOS

### 7.1 Calendario de Jobs

| Job | Frecuencia | Hora | Descripción |
|-----|------------|------|-------------|
| `GenerateMonthlyInvoices` | Diario | 00:01 | Facturación según billing_day |
| `ProcessDunningActions` | Diario | 08:00 | Acciones de cobranza |
| `SendPaymentReminders` | Diario | 09:00 | Recordatorios próximos a vencer |
| `SyncBankStatements` | Diario | 06:00 | Conciliación bancaria |
| `CheckDeviceStatus` | Cada 5 min | - | Monitoreo de equipos |
| `CalculateDebtAging` | Diario | 01:00 | Antigüedad de deuda |
| `CleanupExpiredTokens` | Semanal | Dom 03:00 | Limpiar tokens workflow |
| `GenerateReports` | Mensual | 1° día 00:30 | Reportes gerenciales |
| `BackupDatabase` | Diario | 02:00 | Respaldo automático |

### 7.2 Configuración de Schedule

```php
// app/Console/Kernel.php

protected function schedule(Schedule $schedule): void
{
    // Facturación
    $schedule->job(new GenerateMonthlyInvoices)
        ->dailyAt('00:01')
        ->withoutOverlapping()
        ->onOneServer();
    
    // Cobranza
    $schedule->job(new ProcessDunningActions)
        ->dailyAt('08:00')
        ->withoutOverlapping();
    
    // Recordatorios
    $schedule->job(new SendPaymentReminders)
        ->dailyAt('09:00');
    
    // Monitoreo de red
    $schedule->job(new CheckDeviceStatus)
        ->everyFiveMinutes()
        ->withoutOverlapping();
    
    // Conciliación bancaria
    $schedule->job(new SyncBankStatements)
        ->dailyAt('06:00')
        ->environments(['production']);
    
    // Reportes
    $schedule->job(new GenerateMonthlyReports)
        ->monthlyOn(1, '00:30');
    
    // Mantenimiento
    $schedule->command('telescope:prune --hours=72')->daily();
    $schedule->command('activitylog:clean')->daily();
}
```

---

## 8. DTOs PRINCIPALES

### 8.1 Estructura de DTOs

```php
<?php

namespace Modules\Subscription\DTOs;

final readonly class CreateSubscriptionDTO
{
    public function __construct(
        public int $customerId,
        public int $planId,
        public int $addressId,
        public int $billingDay,
        public ?Carbon $startDate = null,
        public ?int $promotionId = null,
        public array $addons = [],
        public ?string $notes = null,
    ) {}
    
    public static function fromRequest(StoreSubscriptionRequest $request): self
    {
        return new self(
            customerId: $request->validated('customer_id'),
            planId: $request->validated('plan_id'),
            addressId: $request->validated('address_id'),
            billingDay: $request->validated('billing_day'),
            startDate: $request->validated('start_date') 
                ? Carbon::parse($request->validated('start_date')) 
                : null,
            promotionId: $request->validated('promotion_id'),
            addons: $request->validated('addons', []),
            notes: $request->validated('notes'),
        );
    }
    
    public function getPlan(): Plan
    {
        return Plan::findOrFail($this->planId);
    }
}
```

### 8.2 Catálogo de DTOs por Módulo

```
Modules/Crm/DTOs/
├── CreateLeadDTO.php
├── UpdateLeadDTO.php
├── ConvertLeadDTO.php
├── CreateCustomerDTO.php
├── UpdateCustomerDTO.php
└── CreateAddressDTO.php

Modules/Subscription/DTOs/
├── CreateSubscriptionDTO.php
├── UpdateSubscriptionDTO.php
├── ChangePlanDTO.php
├── SuspendSubscriptionDTO.php
└── CancelSubscriptionDTO.php

Modules/FieldOps/DTOs/
├── CreateWorkOrderDTO.php
├── AssignWorkOrderDTO.php
├── CompleteWorkOrderDTO.php
└── SubmitChecklistDTO.php

Modules/Finance/DTOs/
├── CreateInvoiceDTO.php
├── RegisterPaymentDTO.php
├── CreatePromiseToPayDTO.php
└── ProcessRefundDTO.php

Modules/Inventory/DTOs/
├── CreateMovementDTO.php
├── TransferStockDTO.php
└── AssignSerialDTO.php
```

---

## 9. COMPONENTES UI (BLADE + ALPINE)

### 9.1 Biblioteca Base

```
resources/views/components/
├── button.blade.php
├── input.blade.php
├── select.blade.php
├── textarea.blade.php
├── checkbox.blade.php
├── radio.blade.php
├── toggle.blade.php
├── modal.blade.php
├── dropdown.blade.php
├── card.blade.php
├── table.blade.php
├── pagination.blade.php
├── badge.blade.php
├── alert.blade.php
├── toast.blade.php
├── tabs.blade.php
├── accordion.blade.php
├── breadcrumb.blade.php
├── avatar.blade.php
├── stat-card.blade.php
└── empty-state.blade.php
```

### 9.2 Ejemplos de Componentes

```php
{{-- components/button.blade.php --}}
@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'disabled' => false,
    'loading' => false,
])

@php
$variants = [
    'primary' => 'bg-blue-600 hover:bg-blue-700 text-white focus:ring-blue-500',
    'secondary' => 'bg-gray-200 hover:bg-gray-300 text-gray-800 focus:ring-gray-500',
    'danger' => 'bg-red-600 hover:bg-red-700 text-white focus:ring-red-500',
    'success' => 'bg-green-600 hover:bg-green-700 text-white focus:ring-green-500',
    'outline' => 'border-2 border-gray-300 hover:bg-gray-50 text-gray-700',
];

$sizes = [
    'xs' => 'px-2 py-1 text-xs',
    'sm' => 'px-3 py-1.5 text-sm',
    'md' => 'px-4 py-2 text-sm',
    'lg' => 'px-5 py-2.5 text-base',
    'xl' => 'px-6 py-3 text-lg',
];

$classes = $variants[$variant] . ' ' . $sizes[$size];
@endphp

<button 
    type="{{ $type }}"
    {{ $disabled ? 'disabled' : '' }}
    {{ $attributes->merge([
        'class' => "inline-flex items-center justify-center font-medium rounded-lg 
                   focus:outline-none focus:ring-2 focus:ring-offset-2 
                   disabled:opacity-50 disabled:cursor-not-allowed
                   transition-colors duration-150 {$classes}"
    ]) }}
>
    @if($loading)
        <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
    @endif
    {{ $slot }}
</button>


{{-- components/modal.blade.php --}}
@props([
    'name',
    'title' => '',
    'maxWidth' => 'md',
])

@php
$maxWidths = [
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
];
@endphp

<div
    x-data="{ open: false }"
    x-on:open-modal.window="if ($event.detail === '{{ $name }}') open = true"
    x-on:close-modal.window="if ($event.detail === '{{ $name }}') open = false"
    x-on:keydown.escape.window="open = false"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
>
    {{-- Backdrop --}}
    <div 
        x-show="open"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-gray-500 bg-opacity-75"
        @click="open = false"
    ></div>

    {{-- Modal --}}
    <div class="flex min-h-full items-center justify-center p-4">
        <div
            x-show="open"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative bg-white rounded-lg shadow-xl w-full {{ $maxWidths[$maxWidth] }}"
            @click.stop
        >
            @if($title)
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">{{ $title }}</h3>
                </div>
            @endif
            
            <div class="px-6 py-4">
                {{ $slot }}
            </div>
            
            @isset($footer)
                <div class="px-6 py-4 bg-gray-50 rounded-b-lg flex justify-end space-x-3">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>


{{-- components/badge.blade.php --}}
@props([
    'variant' => 'default',
    'size' => 'md',
])

@php
$variants = [
    'default' => 'bg-gray-100 text-gray-800',
    'primary' => 'bg-blue-100 text-blue-800',
    'success' => 'bg-green-100 text-green-800',
    'warning' => 'bg-yellow-100 text-yellow-800',
    'danger' => 'bg-red-100 text-red-800',
    'info' => 'bg-cyan-100 text-cyan-800',
];

$sizes = [
    'sm' => 'px-2 py-0.5 text-xs',
    'md' => 'px-2.5 py-1 text-xs',
    'lg' => 'px-3 py-1 text-sm',
];
@endphp

<span {{ $attributes->merge([
    'class' => "inline-flex items-center font-medium rounded-full {$variants[$variant]} {$sizes[$size]}"
]) }}>
    {{ $slot }}
</span>
```

---

## 10. ORDEN DE IMPLEMENTACIÓN

### Fase 1: Fundamentos (Semanas 1-2)

```
□ 1.1 Configurar proyecto Laravel 11
□ 1.2 Instalar y configurar nwidart/laravel-modules
□ 1.3 Crear módulo Core con traits base
□ 1.4 Crear módulo AccessControl
    □ Migraciones: users, roles, permissions, zones
    □ Seeders: roles y permisos base
    □ Middleware de autenticación
    □ Policies base
□ 1.5 Crear biblioteca de componentes Blade
□ 1.6 Layout principal con navegación
```

### Fase 2: Datos Maestros (Semanas 3-4)

```
□ 2.1 Módulo Network
    □ Migraciones: nodes, devices, ports, nap_boxes, ip_pools
    □ CRUD de nodos y dispositivos
    □ Gestión de pools de IP
□ 2.2 Módulo Inventory
    □ Migraciones: products, warehouses, stock, movements
    □ CRUD de productos y almacenes
    □ Movimientos de inventario
□ 2.3 Módulo Catalog
    □ Migraciones: plans, parameters, promotions, addons
    □ CRUD de planes
```

### Fase 3: Operaciones Core (Semanas 5-7)

```
□ 3.1 Módulo Workflow
    □ Migraciones: workflows, places, transitions, tokens
    □ WorkflowService
    □ Seeders: workflows de instalación y soporte
□ 3.2 Módulo Crm
    □ Migraciones: leads, customers, addresses, contacts
    □ CRUD completo
    □ Conversión de Lead a Customer
□ 3.3 Módulo Subscription
    □ Migraciones: subscriptions, service_instances
    □ Creación de contratos
    □ Integración con Workflow
```

### Fase 4: Procesos de Campo (Semanas 8-9)

```
□ 4.1 Módulo FieldOps
    □ Migraciones: work_orders, appointments, checklists
    □ Flujo completo de instalación
    □ App móvil o PWA para técnicos
□ 4.2 Integración Network + Inventory
    □ Aprovisionamiento automático
    □ Consumo de materiales
```

### Fase 5: Finanzas (Semanas 10-11)

```
□ 5.1 Módulo Finance
    □ Migraciones: invoices, payments, wallets
    □ Generación de facturas
    □ Registro de pagos
    □ Proceso de dunning
□ 5.2 Integraciones de pago
    □ Webhooks de pasarelas
    □ Conciliación bancaria
```

### Fase 6: Integraciones y Pulido (Semanas 12+)

```
□ 6.1 API RouterOS (Mikrotik)
□ 6.2 API OLT (Huawei/ZTE)
□ 6.3 Notificaciones (Email, SMS, WhatsApp)
□ 6.4 Reportes y dashboards
□ 6.5 Testing completo
□ 6.6 Documentación
```

---

## 11. COMANDOS ÚTILES

### 11.1 Módulos

```bash
# Crear módulo
php artisan module:make AccessControl

# Crear componentes del módulo
php artisan module:make-model User AccessControl -m      # Modelo + migración
php artisan module:make-controller UserController AccessControl
php artisan module:make-request StoreUserRequest AccessControl
php artisan module:make-policy UserPolicy AccessControl
php artisan module:make-event UserCreated AccessControl
php artisan module:make-listener SendWelcomeEmail AccessControl
php artisan module:make-job ProcessUser AccessControl
php artisan module:make-seeder UserSeeder AccessControl

# Migraciones
php artisan module:migrate AccessControl
php artisan module:migrate-rollback AccessControl
php artisan module:seed AccessControl
```

### 11.2 Testing

```bash
# Ejecutar tests de un módulo
php artisan test --filter=AccessControl

# Coverage
php artisan test --coverage --min=80

# Tests específicos
php artisan test --filter=SubscriptionServiceTest
```

### 11.3 Mantenimiento

```bash
# Cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Limpiar
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

---

## 12. CONVENCIONES DE CÓDIGO

### 12.1 Nomenclatura

| Elemento | Convención | Ejemplo |
|----------|------------|---------|
| Modelos | Singular, PascalCase | `Customer`, `WorkOrder` |
| Tablas | Plural, snake_case | `customers`, `work_orders` |
| Controladores | Recurso + Controller | `CustomerController` |
| Services | Entidad + Service | `SubscriptionService` |
| Repositories | Entidad + Repository | `CustomerRepository` |
| DTOs | Acción + DTO | `CreateCustomerDTO` |
| Events | PastTense | `CustomerCreated`, `PaymentReceived` |
| Listeners | Acción descriptiva | `SendWelcomeEmail`, `UpdateDebtAging` |
| Jobs | Acción imperativa | `GenerateInvoices`, `ProcessDunning` |
| Policies | Entidad + Policy | `WorkOrderPolicy` |
| Enums | Singular, PascalCase | `SubscriptionStatus`, `PaymentMethod` |

### 12.2 Estructura de Migraciones

```php
Schema::create('subscriptions', function (Blueprint $table) {
    // Identificadores
    $table->id();
    $table->uuid('uuid')->unique();
    $table->string('code', 20)->unique();
    
    // Relaciones
    $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
    $table->foreignId('plan_id')->constrained();
    $table->foreignId('address_id')->constrained();
    
    // Datos principales
    $table->string('status', 30)->default('draft');
    $table->unsignedTinyInteger('billing_day');
    $table->decimal('monthly_price', 10, 2);
    $table->decimal('installation_fee', 10, 2)->default(0);
    
    // Fechas de negocio
    $table->date('start_date')->nullable();
    $table->date('end_date')->nullable();
    
    // Auditoría
    $table->foreignId('created_by')->nullable()->constrained('users');
    $table->timestamps();
    $table->softDeletes();
    
    // Índices
    $table->index('status');
    $table->index('billing_day');
    $table->index(['customer_id', 'status']);
});
```

---

## 13. CHECKLIST PRE-DESARROLLO

Antes de iniciar cada módulo, verificar:

```
□ Migraciones definidas con FK y índices
□ Enums creados para campos de estado
□ DTOs para entrada/salida de datos
□ Events planificados
□ Policies definidas
□ Form Requests con reglas de validación
□ Documentación de API (si aplica)
```

---

*Este documento debe mantenerse actualizado conforme avance el desarrollo.*
