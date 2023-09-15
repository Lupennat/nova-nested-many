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

        $resourceClass::authorizeToCreate($request);

        $request['editing'] = 'true';
        $request['editMode'] = 'update';

        return [
            'resources' => collect($request->nestedChildren())->mapInto($resourceClass)->map->serializeForNestedUpdate($request),
        ];
    }
}
