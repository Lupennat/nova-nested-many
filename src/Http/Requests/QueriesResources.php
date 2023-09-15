<?php

namespace Lupennat\NestedMany\Http\Requests;

trait QueriesResources
{
    /**
     * Get a new query builder for the underlying model.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newQuery()
    {
        if (!$this->viaRelationship()) {
            return $this->model()->newQuery();
        }

        abort_unless($this->newViaResource()->hasRelatableField($this, $this->viaRelationship), 409);

        return forward_static_call([$this->viaResource(), 'newModel'])
            ->newQueryWithoutScopes()->findOrFail(
                $this->viaResourceId
            )->{$this->viaRelationship}();
    }
}
