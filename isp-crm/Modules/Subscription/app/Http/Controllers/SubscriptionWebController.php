<?php

declare(strict_types=1);

namespace Modules\Subscription\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Customer\Entities\Customer;
use Modules\Plan\Entities\Plan;
use Modules\Plan\Entities\Addon;
use Modules\Promotion\Entities\Promotion;
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

        $subscriptions = Subscription::query()
            ->with(['customer', 'plan', 'serviceAddress'])
            ->when($filters['status'] ?? null, fn($q, $status) => $q->where('status', $status))
            ->when($filters['plan_id'] ?? null, fn($q, $planId) => $q->where('plan_id', $planId))
            ->when($filters['billing_cycle'] ?? null, fn($q, $cycle) => $q->where('billing_cycle', $cycle))
            ->when($filters['billing_day'] ?? null, fn($q, $day) => $q->where('billing_day', $day))
            ->when($filters['search'] ?? null, function($q, $search) {
                $q->where('subscription_code', 'like', "%{$search}%")
                  ->orWhereHas('customer', fn($q) => $q->where('name', 'like', "%{$search}%"));
            })
            ->latest()
            ->paginate(15);

        $plans = Plan::where('is_active', true)->get();

        $stats = [
            'active' => Subscription::where('status', 'active')->count(),
            'pending' => Subscription::where('status', 'pending')->count(),
            'suspended' => Subscription::where('status', 'suspended')->count(),
            'monthly_revenue' => Subscription::where('status', 'active')->sum('monthly_price'),
        ];

        return view('subscription::index', compact('subscriptions', 'plans', 'filters', 'stats'));
    }

    public function create(): View
    {
        $customers = Customer::where('is_active', true)->get();
        $plans = Plan::where('is_active', true)->get();
        $addons = Addon::where('is_active', true)->get();
        $promotions = Promotion::where('is_active', true)
            ->where('start_date', '<=', now())
            ->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()))
            ->get();
        $addresses = collect(); // Will be loaded via AJAX based on customer selection

        return view('subscription::create', compact('customers', 'plans', 'addons', 'promotions', 'addresses'));
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
            'promotion_id' => 'nullable|exists:promotions,id',
            'promotion_code' => 'nullable|string',
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
            'serviceAddress',
            'serviceInstance.napPort.napBox',
            'addons',
            'promotion',
            'statusHistory',
            'notes',
        ]);

        return view('subscription::show', compact('subscription'));
    }

    public function edit(Subscription $subscription): View
    {
        $subscription->load(['customer', 'plan', 'serviceAddress']);

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
            'monthly_price' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        try {
            $subscription->update($validated);

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
