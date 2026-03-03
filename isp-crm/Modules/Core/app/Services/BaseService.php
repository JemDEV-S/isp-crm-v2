<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Throwable;

abstract class BaseService
{
    /**
     * Execute a callback within a database transaction.
     *
     * @param callable $callback
     * @return mixed
     * @throws Throwable
     */
    protected function transaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }

    /**
     * Dispatch an event.
     *
     * @param object $event
     * @return void
     */
    protected function dispatchEvent(object $event): void
    {
        Event::dispatch($event);
    }

    /**
     * Log an action or message.
     *
     * @param string $message
     * @param array $context
     * @param string $level
     * @return void
     */
    protected function log(string $message, array $context = [], string $level = 'info'): void
    {
        logger()->{$level}($message, $context);
    }

    /**
     * Log an error.
     *
     * @param string $message
     * @param Throwable|null $exception
     * @param array $context
     * @return void
     */
    protected function logError(string $message, ?Throwable $exception = null, array $context = []): void
    {
        if ($exception) {
            $context['exception'] = $exception->getMessage();
            $context['trace'] = $exception->getTraceAsString();
        }

        $this->log($message, $context, 'error');
    }

    /**
     * Handle exceptions in a standardized way.
     *
     * @param Throwable $exception
     * @param string $context
     * @return void
     */
    protected function handleException(Throwable $exception, string $context): void
    {
        $this->logError("Exception in {$context}", $exception);
    }
}
