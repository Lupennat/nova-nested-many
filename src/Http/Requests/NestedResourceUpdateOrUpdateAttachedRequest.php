<?php

namespace Lupennat\NestedMany\Http\Requests;

use Laravel\Nova\Http\Requests\NovaRequest;

class NestedResourceUpdateOrUpdateAttachedRequest extends NovaRequest implements NestedResourceRequest
{
    use QueriesResources;
    use ChildrenResources;
}
