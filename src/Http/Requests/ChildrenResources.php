<?php

namespace Lupennat\NestedMany\Http\Requests;

use Laravel\Nova\Http\Requests\NovaRequest;
use Lupennat\NestedMany\NestedChildrenHelper;

trait ChildrenResources
{
    /**
     * @var array<int,\Lupennat\NestedMany\Models\Contracts\Nestable>|null
     */
    protected $nestedChildrenResources;

    /**
     * Get all children models.
     *
     * @return array<\Lupennat\NestedMany\Models\Contracts\Nestable>
     */
    public function nestedChildren(): array
    {
        if (is_null($this->nestedChildrenResources)) {

            $resourceClass = $this->resource();

            $children = $this->getProcessedNestedChildren($this, $resourceClass);

            $this->nestedChildrenResources = $this->generateNestedChildren($this, $children, $resourceClass);

            app()->instance(NovaRequest::class, $this);
        }


        return $this->nestedChildrenResources;
    }

    protected function getProcessedNestedChildren($request, $resourceClass)
    {
        return NestedChildrenHelper::getNestedChildrenModelAttributes($request, 'nestedChildren', $resourceClass);
    }

    protected function generateNestedChildren($request, $children, $resourceClass)
    {
        return array_map(fn ($child) => $this->getModel($request, $resourceClass, $child), $children);
    }

    protected function getModel($request, $resourceClass, $child)
    {
        $isNestedDefault = false;

        if (array_key_exists('isNestedDefault', $child['attributes'])) {
            $isNestedDefault = (bool) $child['attributes']['isNestedDefault'];
            unset($child['attributes']['isNestedDefault']);
        }

        $isNestedSoftDeleted = false;

        if (array_key_exists('isNestedSoftDeleted', $child['attributes'])) {
            $isNestedSoftDeleted = (bool) $child['attributes']['isNestedSoftDeleted'];
            unset($child['attributes']['isNestedSoftDeleted']);
        }

        $isNestedActive = false;

        if (array_key_exists('isNestedActive', $child['attributes'])) {
            $isNestedActive = (bool) $child['attributes']['isNestedActive'];
            unset($child['attributes']['isNestedActive']);
        }

        $nestedUid = null;

        if (array_key_exists('nestedUid', $child['attributes'])) {
            $nestedUid = (string) $child['attributes']['nestedUid'];
            unset($child['attributes']['nestedUid']);
        }

        $childRequest = $child['model']->exists ?
        NestedResourceUpdateOrUpdateAttachedRequest::createFrom($request) :
        NestedResourceCreateOrAttachRequest::createFrom($request);

        unset($childRequest['nestedChildren']);

        foreach ($child['attributes'] as $key => $value) {
            $childRequest[$key] = $value;
        }

        $childRequest['editing'] = 'true';
        $childRequest['editMode'] = $child['model']->exists ? 'update' : 'create';

        [$model] = $resourceClass::{$child['model']->exists ? 'nestedFillForUpdate' : 'nestedFill'}(
            $childRequest,
            $child['model']
        );

        return $model->nestedSetDefault($isNestedDefault)
            ->nestedSetSoftDelete($isNestedSoftDeleted)
            ->nestedSetUid($nestedUid)
            ->nestedSetActive($isNestedActive);
    }
}
