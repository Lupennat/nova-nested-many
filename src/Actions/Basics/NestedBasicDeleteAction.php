<?php

namespace Lupennat\NestedMany\Actions\Basics;

use Laravel\Nova\Fields\ActionFields;
use Lupennat\NestedMany\Models\Nested;

class NestedBasicDeleteAction extends NestedBasicAction
{
    /**
     * Determine where the action redirection should be without confirmation.
     *
     * @var bool
     */
    public $withoutConfirmation = true;

    public $destructive = true;

    public function handle(ActionFields $fields, Nested $selected): Nested
    {
        return $selected->delete();
    }
}
