<?php

namespace Lupennat\NestedMany\Http\Resources;

use Laravel\Nova\Http\Resources\Resource;

class NestedEditViewResource extends Resource
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
        $resource = $request->resource();

        $request['editing'] = 'true';
        $request['editMode'] = 'update';

        return [
            'resources' => $request->newQuery()->get()->mapInto($resource)->map(function ($resource, $index) use ($request) {
                return $resource->serializeForNestedUpdate($request, $index);
            }),
        ];
    }
}
