<?php

namespace Lupennat\NestedMany\Actions;

use Illuminate\Support\Collection;
use Laravel\Nova\Fields\ActionFields;

abstract class NestedAction extends NestedBaseAction
{
    /**
     * Perform the action on the given models.
     *
     * @param \Laravel\Nova\Fields\ActionFields $actionFields
     * @param \Illuminate\Support\Collection<int,\Lupennat\NestedMany\Models\Nested> $children
     * @param string|null $selectedUid
     *
     * @return \Illuminate\Support\Collection<int,\Lupennat\NestedMany\Models\Nested>
     */
    abstract public function handle(ActionFields $fields, Collection $children, $selectedUid): Collection;
}
