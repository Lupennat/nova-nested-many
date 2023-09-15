<?php

namespace Lupennat\NestedMany\Http\Controllers;

use Illuminate\Routing\Controller;
use Laravel\Nova\Http\Requests\NovaRequest;
use Lupennat\NestedMany\Actions\Basics\NestedBasicAddAction;
use Lupennat\NestedMany\Actions\Basics\NestedBasicDeleteAction;
use Lupennat\NestedMany\Actions\Basics\NestedBasicRestoreAction;
use Lupennat\NestedMany\Actions\NestedBaseAction;
use Lupennat\NestedMany\Http\Requests\NestedActionRequest;

class NestedActionController extends Controller
{
    /**
     * List the actions for the given resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(NestedActionRequest $request)
    {
        $resource = $request->newResourceWith(
            $request->model()
        );

        return response()->json([
            'actions' => $this->availableActions($request, $resource),
            'addAction' => $this->availableAddAction($request, $resource),
            'deleteAction' => $this->availableDeleteAction($request, $resource),
            'restoreAction' => $this->availableRestoreAction($request, $resource),
        ]);
    }

    /**
     * Perform an action on the specified resources.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(NestedActionRequest $request)
    {
        $request->validateFields();

        return $request->action()->handleRequest($request);
    }

    /**
     * Sync an action field on the specified resources.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function sync(NestedActionRequest $request)
    {
        $action = $this->allAvailableActions($request, $request->newResource())
            ->first(function ($action) use ($request) {
                return $action->uriKey() === $request->query('action');
            });

        abort_unless($action instanceof NestedBaseAction, 404);

        return response()->json(
            collect($action->fields($request))
                ->filter(function ($field) use ($request) {
                    return $request->query('field') === $field->attribute &&
                        $request->query('component') === $field->dependentComponentKey();
                })->each->syncDependsOn($request)
                ->first()
        );
    }

    protected function allAvailableActions(NovaRequest $request, $resource)
    {
        return $this->availableActions($request, $resource)
            ->merge([
                $this->availableAddAction($request, $resource),
                $this->availableDeleteAction($request, $resource),
                $this->availableRestoreAction($request, $resource),
            ]);
    }

    /**
     * Get available actions for request.
     *
     * @param \Laravel\Nova\Resource $resource
     *
     * @return \Illuminate\Support\Collection<int,\Lupennat\NestedMany\Actions\NestedAction>
     */
    protected function availableActions(NovaRequest $request, $resource)
    {
        return $resource->availableNestedActions($request);
    }

    /**
     * Get available actions for request.
     *
     * @param \Laravel\Nova\Resource $resource
     */
    protected function availableAddAction(NovaRequest $request, $resource): NestedBasicAddAction
    {
        return $resource->availableNestedAddAction($request);
    }

    /**
     * Get available actions for request.
     *
     * @param \Laravel\Nova\Resource $resource
     */
    protected function availableDeleteAction(NovaRequest $request, $resource): NestedBasicDeleteAction
    {
        return $resource->availableNestedDeleteAction($request);
    }

    /**
     * Get available actions for request.
     *
     * @param \Laravel\Nova\Resource $resource
     */
    protected function availableRestoreAction(NovaRequest $request, $resource): NestedBasicRestoreAction
    {
        return $resource->availableNestedRestoreAction($request);
    }
}
