<?php

namespace Lupennat\NestedMany\Http\Requests;

use Laravel\Nova\Http\Requests\ActionRequest;
use Lupennat\NestedMany\Models\Nested;

class NestedActionRequest extends ActionRequest implements NestedResourceRequest
{
    use ChildrenResources;

    /**
     * generate new Nested.
     */
    public function newNested(): Nested
    {
        return new Nested($this->model(), true);
    }

    /**
     * Get the action instance specified by the request.
     *
     * @return \Lupennat\NestedMany\Actions\NestedBaseAction
     */
    public function action()
    {
        return once(function () {
            $hasResources = !empty($this->nestedResources);

            return $this->availableActions()
                ->filter(function ($action) use ($hasResources) {
                    return $hasResources ? true : $action->isStandalone();
                })->first(function ($action) {
                    return $action->uriKey() == $this->query('action');
                }) ?: abort($this->actionExists() ? 403 : 404);
        });
    }

    /**
     * Get the all actions for the request.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function resolveActions()
    {
        return $this->newResource()->resolveNestedActions($this)
            ->merge([
                $this->newResource()->resolveNestedAddAction($this),
                $this->newResource()->resolveNestedDeleteAction($this),
                $this->newResource()->resolveNestedRestoreAction($this),
            ]);
    }
}
