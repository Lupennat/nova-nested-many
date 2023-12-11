<?php

namespace Lupennat\NestedMany\Http\Requests;

use Laravel\Nova\Http\Requests\NovaRequest;
use Lupennat\NestedMany\NestedChildrenable;

trait ChildrenResources
{
    use NestedChildrenable;

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
            $this->nestedChildrenResources = [];

            $resourceClass = $this->resource();

            $children = static::getNestedChildrenModelAttributes($this, 'nestedChildren', $resourceClass);

            foreach ($children as $child) {
                $this->nestedChildrenResources[] = $this->getModel($resourceClass, $child);
            }

            app()->instance(NovaRequest::class, $this);
        }

        return $this->nestedChildrenResources;
    }

    protected function getModel($resourceClass, $child)
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
        NestedResourceUpdateOrUpdateAttachedRequest::createFrom($this) :
        NestedResourceCreateOrAttachRequest::createFrom($this);

        unset($childRequest['nestedChildren']);

        foreach ($child['attributes'] as $key => $value) {
            $childRequest[$key] = $value;
        }

        $childRequest['editing'] = 'true';
        $childRequest['editMode'] = $child['model']->exists ? 'update' : 'create';

        [$model] = $resourceClass::{$child['model']->exists ? 'fillForUpdate' : 'fill'}(
            $childRequest,
            $child['model']
        );

        return $model->nestedSetDefault($isNestedDefault)
            ->nestedSetSoftDelete($isNestedSoftDeleted)
            ->nestedSetUid($nestedUid)
            ->nestedSetActive($isNestedActive);
    }
}
