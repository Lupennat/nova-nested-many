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

    public $confirmText = 'This operation cannot be undone, are you sure you want to remove?';

    public $confirmButtonText = 'Remove';

    /**
     * Perform the action on the given models.
     *
     * @param \Laravel\Nova\Fields\ActionFields $actionFields
     * @param \Lupennat\NestedMany\Models\Nested $selectedUid
     *
     * @return \Lupennat\NestedMany\Models\Nested
     */
    public function handle(ActionFields $fields, Nested $selected): Nested
    {
        return $selected->delete()->active();
    }
}
