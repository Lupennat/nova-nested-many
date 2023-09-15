<?php

namespace Lupennat\NestedMany\Actions\Basics;

use Laravel\Nova\Fields\ActionFields;
use Lupennat\NestedMany\Models\Nested;

class NestedBasicRestoreAction extends NestedBasicAction
{
    /**
     * Determine where the action redirection should be without confirmation.
     *
     * @var bool
     */
    public $withoutConfirmation = true;

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
        return $selected->restore()->active();
    }
}
