<?php

namespace Lupennat\NestedMany\Actions\Basics;

use Laravel\Nova\Fields\ActionFields;
use Lupennat\NestedMany\Actions\NestedBaseAction;
use Lupennat\NestedMany\Models\Nested;

abstract class NestedBasicAction extends NestedBaseAction
{
    /**
     * Perform the action on the given models.
     *
     * @param \Laravel\Nova\Fields\ActionFields $actionFields
     * @param Nested|null $selectedUid
     *
     * @return \Illuminate\Support\Collection<int,\Lupennat\NestedMany\Models\Nested>
     */
    abstract public function handle(ActionFields $fields, $selected): Nested;

    /**
     * Prepare the action for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), ['basic' => true]);
    }
}
