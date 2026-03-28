<?php

namespace Modules\Finance\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Crm\Events\CustomerCreated;
use Modules\Finance\Events\DunningStageTriggered;
use Modules\Finance\Events\InvoiceBatchCompleted;
use Modules\Finance\Events\InvoiceDisputeOpened;
use Modules\Finance\Events\InvoiceDisputeResolved;
use Modules\Finance\Events\InvoiceGenerated;
use Modules\Finance\Events\InvoiceTaxSubmissionFailed;
use Modules\Finance\Events\InvoicePaid;
use Modules\Finance\Events\PaymentReceived;
use Modules\Finance\Events\PromiseToPayBroken;
use Modules\Finance\Events\PromiseToPayCreated;
use Modules\Finance\Events\ReconnectionCompleted;
use Modules\Finance\Listeners\AllocatePaymentToInvoice;
use Modules\Finance\Listeners\CreateCollectionCaseOnWriteOff;
use Modules\Finance\Listeners\CreateWalletForCustomer;
use Modules\Finance\Listeners\EvaluateReconnection;
use Modules\Finance\Listeners\FulfillPromiseOnPayment;
use Modules\Finance\Listeners\GenerateInitialInvoice;
use Modules\Finance\Listeners\HandleTaxFailure;
use Modules\Finance\Listeners\LogBillingCompletion;
use Modules\Finance\Listeners\LogWebhookResult;
use Modules\Finance\Listeners\NotifyCustomerOnDunning;
use Modules\Finance\Listeners\NotifyPaymentReceived;
use Modules\Finance\Listeners\NotifyReconnection;
use Modules\Finance\Listeners\PauseDunningOnDispute;
use Modules\Finance\Listeners\PauseDunningOnPromise;
use Modules\Finance\Listeners\ResumeDunningOnBrokenPromise;
use Modules\Finance\Listeners\ResumeDunningOnDisputeResolved;
use Modules\Finance\Listeners\SchedulePaymentReminders;
use Modules\Finance\Listeners\SendInvoiceNotification;
use Modules\Finance\Listeners\UpdateDebtAging;
use Modules\Finance\Listeners\UpdateDunningOnPayment;
use Modules\Subscription\Events\SubscriptionActivated;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        CustomerCreated::class => [
            CreateWalletForCustomer::class,
        ],
        SubscriptionActivated::class => [
            GenerateInitialInvoice::class,
        ],
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

        // Dunning events
        DunningStageTriggered::class => [
            NotifyCustomerOnDunning::class,
            CreateCollectionCaseOnWriteOff::class,
        ],

        // Promise events
        PromiseToPayCreated::class => [
            PauseDunningOnPromise::class,
        ],
        PromiseToPayBroken::class => [
            ResumeDunningOnBrokenPromise::class,
        ],

        // Dispute events
        InvoiceDisputeOpened::class => [
            PauseDunningOnDispute::class,
        ],
        InvoiceDisputeResolved::class => [
            ResumeDunningOnDisputeResolved::class,
        ],

        // Payment events
        PaymentReceived::class => [
            AllocatePaymentToInvoice::class,
            NotifyPaymentReceived::class,
            LogWebhookResult::class,
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

    protected static $shouldDiscoverEvents = false;
}
