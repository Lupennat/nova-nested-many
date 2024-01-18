<?php

namespace Lupennat\NestedMany\Http\Resources;

use Laravel\Nova\Http\Resources\Resource;

class NestedDefaultViewResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Lupennat\NestedMany\Http\Requests\NestedResourceUpdateOrUpdateAttachedRequest $request
     *
     * @return array
     */
    public function toArray($request)
    {
        $resourceClass = $request->resource();

        if (!$resourceClass::authorizedToCreateNested($request)) {
            return [
                'resources' => [],
            ];
        }

        $request['editing'] = 'true';
        $request['editMode'] = 'update';
        $request['nestedManagedByParent'] = 'true';

        return [
            'resources' => collect($request->nestedChildren())->mapInto($resourceClass)->map(function ($resource, $index) use ($request) {
                return $resource->serializeForNestedUpdate($request, $index);
            }),
        ];
    }
}
