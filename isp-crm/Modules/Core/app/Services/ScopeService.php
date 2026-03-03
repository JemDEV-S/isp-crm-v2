<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ScopeService
{
    /**
     * Map of models to their scope fields.
     * This will be populated by individual modules.
     */
    private array $scopeMap = [];

    /**
     * Register scope constraints for a model.
     */
    public function register(string $modelClass, array $constraints): void
    {
        $this->scopeMap[$modelClass] = $constraints;
    }

    /**
     * Apply scope to a query based on user permissions.
     */
    public function applyScope(Builder $builder, $user, string $modelClass): void
    {
        // If no scope configuration exists for this model, skip
        if (!isset($this->scopeMap[$modelClass])) {
            return;
        }

        $modelName = class_basename($modelClass);
        $module = $this->getModuleFromModel($modelClass);

        // Base permission format: {module}.{model}.view
        $basePermission = strtolower("{$module}.{$modelName}.view");

        // If user has "view.all" permission, no restrictions
        if ($this->hasPermission($user, "{$basePermission}.all")) {
            return;
        }

        $builder->where(function ($query) use ($user, $basePermission, $modelClass) {
            $hasCondition = false;

            // Check for zone-based permission
            if ($this->hasPermission($user, "{$basePermission}.zone") && $user->zone_id) {
                $this->applyZoneScope($query, $user);
                $hasCondition = true;
            }

            // Check for ownership-based permission
            if ($this->hasPermission($user, "{$basePermission}.own")) {
                $ownerField = $this->getOwnerField($modelClass);
                if ($ownerField) {
                    $query->orWhere($ownerField, $user->id);
                    $hasCondition = true;
                }
            }

            // If no conditions were applied, deny all access
            if (!$hasCondition) {
                $query->whereRaw('1 = 0');
            }
        });
    }

    /**
     * Apply zone-based scope to the query.
     */
    protected function applyZoneScope(Builder $query, $user): void
    {
        // Direct zone filter
        $query->where(function ($q) use ($user) {
            $q->where('zone_id', $user->zone_id);

            // Include child zones if they exist (will be implemented later in AccessControl module)
            // This is a placeholder for the zone hierarchy functionality
        });
    }

    /**
     * Get the owner field for a model.
     */
    protected function getOwnerField(string $modelClass): ?string
    {
        $constraints = $this->scopeMap[$modelClass] ?? [];

        // Check for common owner fields
        foreach (['assigned_to', 'created_by', 'user_id'] as $field) {
            if (in_array($field, $constraints)) {
                return $field;
            }
        }

        return null;
    }

    /**
     * Get the module name from the model class.
     */
    protected function getModuleFromModel(string $modelClass): string
    {
        // Extract module from namespace: Modules\{Module}\...
        $parts = explode('\\', $modelClass);

        if (count($parts) >= 2 && $parts[0] === 'Modules') {
            return strtolower($parts[1]);
        }

        return 'app';
    }

    /**
     * Check if user has a specific permission.
     * This is a placeholder - will be properly implemented in AccessControl module.
     */
    protected function hasPermission($user, string $permission): bool
    {
        if (!$user) {
            return false;
        }

        // Placeholder implementation
        // This will be replaced with proper permission checking from AccessControl module
        if (method_exists($user, 'hasPermission')) {
            return $user->hasPermission($permission);
        }

        if (method_exists($user, 'can')) {
            return $user->can($permission);
        }

        return false;
    }

    /**
     * Get all registered scope mappings.
     */
    public function getScopeMap(): array
    {
        return $this->scopeMap;
    }
}
