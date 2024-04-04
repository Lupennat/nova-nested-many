<?php

namespace Lupennat\NestedMany\Http\Resources;

use Laravel\Nova\Http\Resources\Resource;

class NestedUpdateViewResource extends Resource
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

        return [
            'resources' => collect($request->nestedChildren())->mapInto($resourceClass)->map(function ($resource, $index) use ($request) {
                $request['editing'] = 'true';
                $request['editMode'] = !$resource->resource->exists && !$resource->resource->isNestedDefault() ? 'create' : 'update';
                $request['nestedManagedByParent'] = 'true';

                // readonly is resolved using app request on jsonserialize
                // we need to serialize for each element that way editMode is preserved
                return json_decode(json_encode(!$resource->resource->exists && !$resource->resource->isNestedDefault() ?
                $resource->serializeForNestedCreate($request, $index) :
                $resource->serializeForNestedUpdate($request, $index)), true);
            }),
        ];
    }
}
