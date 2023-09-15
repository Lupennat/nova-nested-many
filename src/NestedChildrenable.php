<?php

namespace Lupennat\NestedMany;

use Laravel\Nova\Http\Requests\NovaRequest;

trait NestedChildrenable
{
    /**
     * @var array<int,array<string,mixed>>
     */
    private static $nestedChildrenFromRequest;

    /**
     * Get nested children from request.
     *
     * @return array<int,array<string,mixed>>
     */
    private static function nestedChildrenFromRequest(NovaRequest $request, string $attribute): array
    {
        if (is_null(static::$nestedChildrenFromRequest)) {
            static::$nestedChildrenFromRequest = is_array($request->{$attribute}) ? $request->{$attribute} : [];
        }

        return static::$nestedChildrenFromRequest;
    }

    /**
     * Get Nested children model and attributes from request.
     *
     * @param class-string<\Laravel\Nova\Resource>
     *
     * @return array<int,array>
     */
    public static function getNestedChildrenModelAttributes(NovaRequest $request, string $attribute, string $resourceClass)
    {
        $children = static::nestedChildrenFromRequest($request, $attribute);

        $nestedChildrenResources = [];

        $primaryKey = $resourceClass::newModel()->getKeyName();

        $primarayKeyValues = array_reduce($children, function ($carry, $child) use ($primaryKey) {
            if (array_key_exists($primaryKey, $child) && $child[$primaryKey]) {
                $carry[] = $child[$primaryKey];
            }

            return $carry;
        }, []);

        $existingResources = collect([]);

        if (count($primarayKeyValues)) {
            $existingResources = $resourceClass::$model::whereIn($primaryKey, $primarayKeyValues)->get();
        }

        foreach ($children as $child) {
            $childPrimaryKeyValue = array_key_exists($primaryKey, $child) ? $child[$primaryKey] : null;

            $model = ($childPrimaryKeyValue ? $existingResources->where($primaryKey, $childPrimaryKeyValue)->first() : null) ?? $resourceClass::newModel();

            $nestedChildrenResources[] = [
                'model' => $model,
                'attributes' => $child,
            ];
        }

        return $nestedChildrenResources;
    }

    /**
     * Count nested children.
     */
    public static function countNestedChildren(NovaRequest $request, string $attribute): int
    {
        return count(static::nestedChildrenFromRequest($request, $attribute));
    }
}
