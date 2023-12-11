<?php

namespace Lupennat\NestedMany\Fields;

use Laravel\Nova\Http\Requests\NovaRequest;
use Lupennat\NestedMany\Http\Controllers\NestedController;
use Lupennat\NestedMany\Http\Requests\NestedResourceUpdateOrUpdateAttachedRequest;

trait NestedRecursive
{
    /**
     * The field's preloaded resources.
     */
    public $resources = [];

    /**
     * Generate resources from parent.
     */
    public function generateResourcesFromParent(NovaRequest $request, $index): void
    {
        $resources = [];
        $parent = $request->nestedChildren[$index] ?? null;

        if ($parent) {
            $keyName = $request->model()->getKeyName();
            $children = $parent[$this->validationKey()] ?? [];

            if (count($children)) {
                $oldResourceName = $request->route('resource');
                $request->route()->setParameter('resource', $this->resourceName);
                $newRequest = NovaRequest::createFrom($request);

                $updateRequest = NestedResourceUpdateOrUpdateAttachedRequest::createFrom($newRequest->replace([
                    'nestedChildren' => $children,
                    'viaResource' => $oldResourceName,
                    'viaResourceId' => $parent[$keyName] ?? null,
                    'viaRelationship' => $this->attribute,
                ]));

                $resources = (new NestedController())->updateResources($updateRequest)->getData(true)['resources'] ?? [];

                $request->route()->setParameter('resource', $oldResourceName);
            }
        }

        $this->resources = $resources;
    }
}
