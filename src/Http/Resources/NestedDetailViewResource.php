<?php

namespace Lupennat\NestedMany\Http\Resources;

use Laravel\Nova\Http\Resources\Resource;
use Lupennat\NestedMany\Http\Requests\NestedResourceRequest;

class NestedDetailViewResource extends Resource implements NestedResourceRequest
{
    /**
     * Transform the resource into an array.
     *
     * @param \Lupennat\NestedMany\Http\Requests\NestedResourceDetailRequest $request
     *
     * @return array
     */
    public function toArray($request)
    {
        $resource = $request->resource();

        return [
            'label' => $resource::label(),
            'resources' => $request->newQuery()->get()->mapInto($resource)->map->serializeForNestedDetail($request),
        ];
    }
}
