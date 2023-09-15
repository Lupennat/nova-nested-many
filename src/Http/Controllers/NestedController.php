<?php

namespace Lupennat\NestedMany\Http\Controllers;

use Illuminate\Routing\Controller;
use Lupennat\NestedMany\Http\Requests\NestedResourceDetailRequest;
use Lupennat\NestedMany\Http\Requests\NestedResourceUpdateOrUpdateAttachedRequest;
use Lupennat\NestedMany\Http\Resources\NestedDefaultViewResource;
use Lupennat\NestedMany\Http\Resources\NestedDetailViewResource;
use Lupennat\NestedMany\Http\Resources\NestedEditViewResource;
use Lupennat\NestedMany\Http\Resources\NestedUpdateViewResource;

class NestedController extends Controller
{
    /**
     * List the resources for detail.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function detailResources(NestedResourceDetailRequest $request)
    {
        return NestedDetailViewResource::make()->toResponse($request);
    }

    /**
     * List the resources for update.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function editResources(NestedResourceUpdateOrUpdateAttachedRequest $request)
    {
        return NestedEditViewResource::make()->toResponse($request);
    }

    /**
     * List the resources for create.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function defaultResources(NestedResourceUpdateOrUpdateAttachedRequest $request)
    {
        return NestedDefaultViewResource::make()->toResponse($request);
    }

    /**
     * Get the updatedd resource.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function updateResources(NestedResourceUpdateOrUpdateAttachedRequest $request)
    {
        return NestedUpdateViewResource::make()->toResponse($request);
    }
}
