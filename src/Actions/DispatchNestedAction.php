<?php

namespace Lupennat\NestedMany\Actions;

use Laravel\Nova\Fields\ActionFields;
use Lupennat\NestedMany\Actions\Basics\NestedBasicAction;
use Lupennat\NestedMany\Http\Requests\NestedActionRequest;
use Lupennat\NestedMany\Models\Nested;

class DispatchNestedAction
{
    /**
     * The request instance.
     *
     * @var \Laravel\Nova\Http\Requests\ActionRequest
     */
    protected $request;

    /**
     * The action instance.
     *
     * @var \Lupennat\NestedMany\Actions\NestedBaseAction
     */
    protected $action;

    /**
     * The fields for action instance.
     *
     * @var \Laravel\Nova\Fields\ActionFields
     */
    protected $fields;

    /**
     * Set dispatchable callback.
     *
     * @var (callable(Lupennat\NestedMany\Actions\NestedResponse):(mixed))|null
     */
    protected $dispatchableCallback;

    /**
     * Create a new action dispatcher instance.
     *
     * @return void
     */
    public function __construct(NestedActionRequest $request, NestedBaseAction $action, ActionFields $fields)
    {
        $this->request = $request;
        $this->action = $action;
        $this->fields = $fields;
    }

    /**
     * Dispatch the given action.
     *
     * @param string $method
     *
     * @return $this
     *
     * @throws \Throwable
     */
    public function handleRequest(NestedActionRequest $request, $method)
    {
        if ($this->action instanceof NestedBasicAction) {
            $this->dispatchableCallback = function (NestedResponse $response) use ($request, $method) {
                $children = collect($request->nestedChildren())->map(fn ($model) => $model->getNestedItem());
                $resource = is_array($request->nestedResources) && count($request->nestedResources) ? $request->nestedResources[0] : null;

                $nested = $this->action->{$method}(
                    $this->fields,
                    $resource ? $children->where(Nested::UIDFIELD, $resource)->first() : null
                );

                return $response->successfulNested($request, $nested);
            };
        } else {
            $this->dispatchableCallback = function (NestedResponse $response) use ($request, $method) {
                $results = $this->action->{$method}(
                    $this->fields,
                    collect($request->nestedChildren())->map(fn ($model) => $model->getNestedItem()),
                    is_array($request->nestedResources) && count($request->nestedResources) ? $request->nestedResources[0] : []
                );

                return $response->successfulCollection($request, $results);
            };
        }

        return $this;
    }

    /**
     * Dispatch the action.
     *
     * @return Lupennat\NestedMany\Actions\NestedResponse
     *
     * @throws \Throwable
     */
    public function dispatch()
    {
        return with(new NestedResponse(), $this->dispatchableCallback);
    }

    /**
     * Dispatch the given action using custom handler.
     *
     * @param  \Closure(\Laravel\Nova\Http\Requests\ActionRequest, \Lupennat\NestedMany\Actions\NestedResponse, \Laravel\Nova\Fields\ActionFields):Lupennat\NestedMany\Actions\NestedResponse  $callback
     *
     * @return $this
     */
    public function handleUsing(NestedActionRequest $request, $callback)
    {
        $this->dispatchableCallback = function (NestedResponse $response) use ($request, $callback) {
            return $callback($request, $response, $this->fields);
        };

        return $this;
    }
}
