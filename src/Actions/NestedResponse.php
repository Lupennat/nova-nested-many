<?php

namespace Lupennat\NestedMany\Actions;

use Illuminate\Support\Collection;
use Lupennat\NestedMany\Http\Requests\NestedActionRequest;
use Lupennat\NestedMany\Models\Nested;

class NestedResponse
{
    /**
     * List of action results.
     *
     * @var array
     */
    public $results = [];

    /**
     * Mark response as successful.
     *
     * @return $this
     */
    public function successfulNested(NestedActionRequest $request, Nested $nested)
    {
        if ($nested->isDeleted() && !$nested->hasSoftDelete()) {
            $this->results['resource'] = null;

            return $this;
        }

        $this->results['resource'] = $this->generateResourceFromNested($nested, $request->resource(), $request, 0);

        return $this;
    }

    /**
     * Mark response as successful.
     *
     * @return $this
     */
    public function successfulCollection(NestedActionRequest $request, Collection $collection)
    {
        $resource = $request->resource();

        $this->results['resources'] = $collection
            ->reject(function (Nested $nested) {
                return $nested->isDeleted() && !$nested->hasSoftDelete();
            })
            ->map(function (Nested $nested) use ($resource, $request) {
                return $this->generateResourceFromNested($nested, $resource, $request);
            })
            ->values()
            ->toArray();

        return $this;
    }

    protected function generateResourceFromNested(Nested $nested, $resource, $request)
    {
        $model = $nested->toModel();

        $resource = new $resource($model);

        $request['editing'] = 'true';
        $request['editMode'] = !$model->exists && !$model->isNestedDefault() ? 'create' : 'update';
        $request['nestedManagedByParent'] = 'true';

        // readonly is resolved using app request on jsonserialize
        // we need to serialize for each element that way editMode is preserved
        return json_decode(json_encode(!$model->exists && !$model->isNestedDefault() ?
        $resource->serializeForNestedCreate($request) :
        $resource->serializeForNestedUpdate($request)), true);
    }
}
