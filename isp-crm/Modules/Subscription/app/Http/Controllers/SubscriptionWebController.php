<?php

declare(strict_types=1);

namespace Modules\Subscription\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Catalog\Entities\Addon;
use Modules\Catalog\Entities\Plan;
use Modules\Catalog\Entities\Promotion;
use Modules\Crm\Entities\Customer;
use Modules\Subscription\DTOs\CreateSubscriptionDTO;
use Modules\Subscription\Entities\Subscription;
use Modules\Subscription\Services\SubscriptionService;

class SubscriptionWebController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['status', 'customer_id', 'plan_id', 'billing_day', 'billing_cycle', 'search']);

        $subscriptions = $this->subscriptionService->paginate($filters, 15);
        $plans = Plan::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $stats = $this->subscriptionService->getStats() + [
            'monthly_revenue' => Subscription::query()->where('status', 'active')->sum('monthly_price'),
        ];

        return view('subscription::index', compact('subscriptions', 'plans', 'filters', 'stats'));
    }

    public function create(): View
    {
        $customers = Customer::query()
            ->where('is_active', true)
            ->with(['addresses' => fn ($query) => $query->service()->orderByDesc('is_default')->orderBy('id')])
            ->orderBy('name')
            ->get();
        $plans = Plan::query()->where('is_active', true)->orderBy('name')->get();
        $addons = Addon::query()->where('is_active', true)->orderBy('name')->get();
        $promotions = Promotion::query()
            ->valid()
            ->orderBy('name')
            ->get();

        $addressesByCustomer = $customers
            ->mapWithKeys(fn (Customer $customer) => [
                $customer->id => $customer->addresses->map(fn ($address) => [
                    'id' => $address->id,
                    'label' => $address->getFullAddress(),
                    'is_default' => (bool) $address->is_default,
                ])->values()->all(),
            ])
            ->all();

        return view('subscription::create', compact('customers', 'plans', 'addons', 'promotions', 'addressesByCustomer'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'plan_id' => 'required|exists:plans,id',
            'service_address_id' => 'required|exists:addresses,id',
            'billing_day' => 'required|integer|min:1|max:28',
            'billing_cycle' => 'required|string|in:monthly,quarterly,semiannual,annual',
            'start_date' => 'nullable|date',
            'contracted_months' => 'nullable|integer|min:1|max:60',
            'promotion_id' => 'nullable|exists:promotions,id',
            'addon_ids' => 'nullable|array',
            'addon_ids.*' => 'exists:addons,id',
            'notes' => 'nullable|string',
        ]);

        try {
            $subscription = $this->subscriptionService->create(CreateSubscriptionDTO::fromArray([
                'customer_id' => $validated['customer_id'],
                'plan_id' => $validated['plan_id'],
                'address_id' => $validated['service_address_id'],
                'billing_day' => $validated['billing_day'],
                'billing_cycle' => $validated['billing_cycle'],
                'start_date' => $validated['start_date'] ?? null,
                'promotion_id' => $validated['promotion_id'] ?? null,
                'addons' => $validated['addon_ids'] ?? [],
                'contracted_months' => $validated['contracted_months'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]));

            return redirect()
                ->route('subscriptions.show', $subscription)
                ->with('success', 'Suscripción creada exitosamente.');
        } catch (\DomainException $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show(Subscription $subscription): View
    {
        $subscription->load([
            'customer',
            'plan',
            'address.zone',
            'serviceInstance.ipAddress',
            'serviceInstance.napPort.napBox',
            'addons',
            'promotion',
            'statusHistory.user',
            'notes.user',
            'planChangeRequests',
        ]);

        return view('subscription::show', compact('subscription'));
    }

    public function edit(Subscription $subscription): View
    {
        $subscription->load(['customer.addresses', 'plan', 'address']);

        $addresses = $subscription->customer->addresses;

        return view('subscription::edit', compact('subscription', 'addresses'));
    }

    public function update(Request $request, Subscription $subscription): RedirectResponse
    {
        $validated = $request->validate([
            'service_address_id' => 'required|exists:addresses,id',
            'billing_day' => 'required|integer|min:1|max:28',
            'billing_cycle' => 'required|string|in:monthly,quarterly,semiannual,annual',
            'start_date' => 'nullable|date',
            'contracted_months' => 'nullable|integer|min:1|max:60',
            'monthly_price' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        try {
            $updateData = [
                'address_id' => $validated['service_address_id'],
                'billing_day' => $validated['billing_day'],
                'billing_cycle' => $validated['billing_cycle'],
                'start_date' => $validated['start_date'] ?? null,
                'contracted_months' => $validated['contracted_months'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ];

            if (array_key_exists('monthly_price', $validated) && $validated['monthly_price'] !== null) {
                $updateData['monthly_price'] = $validated['monthly_price'];
            }

            if (array_key_exists('discount_percentage', $validated) && $validated['discount_percentage'] !== null) {
                $updateData['discount_percentage'] = $validated['discount_percentage'];
            }

            $subscription->update($updateData);

            return redirect()
                ->route('subscriptions.show', $subscription)
                ->with('success', 'Suscripción actualizada exitosamente.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Error al actualizar la suscripción: ' . $e->getMessage()]);
        }
    }

    public function destroy(Subscription $subscription): RedirectResponse
    {
        try {
            $subscription->delete();

            return redirect()
                ->route('subscriptions.index')
                ->with('success', 'Suscripción eliminada exitosamente.');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Error al eliminar la suscripción: ' . $e->getMessage()]);
        }
    }

    public function activate(Subscription $subscription): RedirectResponse
    {
        try {
            $this->subscriptionService->activate($subscription);

            return back()->with('success', 'Suscripción activada exitosamente.');
        } catch (\DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function suspend(Request $request, Subscription $subscription): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string',
        ]);

        try {
            $this->subscriptionService->suspend($subscription, $validated['reason'] ?? null);

            return back()->with('success', 'Suscripción suspendida exitosamente.');
        } catch (\DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function reactivate(Subscription $subscription): RedirectResponse
    {
        try {
            $this->subscriptionService->reactivate($subscription);

            return back()->with('success', 'Suscripción reactivada exitosamente.');
        } catch (\DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function cancel(Request $request, Subscription $subscription): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string',
        ]);

        try {
            $this->subscriptionService->cancel($subscription, $validated['reason'] ?? null);

            return back()->with('success', 'Suscripción cancelada exitosamente.');
        } catch (\DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
