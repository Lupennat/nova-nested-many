<?php

namespace Lupennat\NestedMany\Actions\Basics;

use Laravel\Nova\Fields\ActionFields;
use Lupennat\NestedMany\Models\Nested;

class NestedBasicAddAction extends NestedBasicAction
{
    /**
     * Determine where the action redirection should be without confirmation.
     *
     * @var bool
     */
    public $withoutConfirmation = true;

    public $standalone = true;

    /**
     * Perform the action on the given models.
     *
     * @param \Laravel\Nova\Fields\ActionFields $actionFields
     *
     * @return \Lupennat\NestedMany\Models\Nested
     */
    public function handle(ActionFields $fields): Nested
    {
        return $this->getNewNested()->active();
    }
}
