<?php

namespace Lupennat\NestedMany\Http\Requests;

use Laravel\Nova\Http\Requests\NovaRequest;

class NestedResourceCreateOrAttachRequest extends NovaRequest implements NestedResourceRequest
{
    use ChildrenResources;
}
