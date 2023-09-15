<?php

namespace Lupennat\NestedMany\Http\Requests;

use Laravel\Nova\Http\Requests\ResourceDetailRequest;

class NestedResourceDetailRequest extends ResourceDetailRequest implements NestedResourceRequest
{
    use QueriesResources;
}
