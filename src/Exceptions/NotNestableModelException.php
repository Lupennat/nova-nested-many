<?php

namespace Lupennat\NestedMany\Exceptions;

class NotNestableModelException extends \Exception
{
    /**
     * @param \Illuminate\Database\Eloquent\Model|string $model
     */
    public function __construct($model)
    {
        parent::__construct(
            __('[:model] must implements \Lupennat\NestedMany\Models\Contracts interface.', ['model' => is_string($model) ? $model : get_class($model)])
        );
    }
}
