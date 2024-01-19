<?php

namespace Lupennat\NestedMany;

use Laravel\Nova\Http\Requests\NovaRequest;

class NestedChildrenHelper
{
    /**
     * Get nested children from request.
     *
     * @return array<int,array<string,mixed>>
     */
    private static function nestedChildrenFromRequest(NovaRequest $request, string $attribute, string $resourceClass): array
    {
        return is_array($request->{$attribute}) ? $request->{$attribute} : [];
    }

    /**
     * Get Nested children model and attributes from request.
     *
     * @param class-string<\Laravel\Nova\Resource>
     *
     * @return array<int,array>
     */
    public static function getNestedChildrenModelAttributes(NovaRequest $request, string $attribute, string $resourceClass, bool $withRelations = false)
    {
        $children = static::nestedChildrenFromRequest($request, $attribute, $resourceClass);

        return static::generateNestedChildrenModelAttributes($children, $resourceClass, $withRelations);


    }

    /**
 * Generate Nested children model and attributes from children.
     *
     * @param array<int,array<string,mixed>> $children
     *
     * @return array<int,array>
     */
    protected static function generateNestedChildrenModelAttributes($children, string $resourceClass, bool $withRelations)
    {
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

            $relations = [];

            if(array_key_exists('nestedManyFields', $child)) {
                $nestedRelations = (array) $child['nestedManyFields'];
                if($withRelations) {
                    foreach($nestedRelations as $attribute => $options) {
                        $relations[$attribute] = [
                            'resourceClass' => $options['resourceClass'],
                            'resourceName' => $options['resourceName'],
                            'relationShip' => $options['relationShip'],
                            'children' => []
                        ];
                        $relations[$attribute]['children'] = array_key_exists($attribute, $child) ? static::generateNestedChildrenModelAttributes((array) $child[$attribute], $options['resourceClass'], true) : [];
                    }
                }
                unset($child['nestedManyFields']);
            }


            $nestedChildrenResources[] = [
                'model' => $model,
                'attributes' => $child,
                'relations' => $relations
            ];
        }

        return $nestedChildrenResources;
    }

    /**
     * Count nested children.
     */
    public static function countNestedChildren(NovaRequest $request, string $attribute, string $resourceClass): int
    {
        return count(static::nestedChildrenFromRequest($request, $attribute, $resourceClass));
    }
}
