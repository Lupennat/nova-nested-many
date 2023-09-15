<?php

namespace Lupennat\NestedMany\Actions\Basics;

use Laravel\Nova\Fields\ActionFields;
use Lupennat\NestedMany\Actions\NestedBaseAction;
use Lupennat\NestedMany\Models\Nested;

abstract class NestedBasicAction extends NestedBaseAction
{

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
