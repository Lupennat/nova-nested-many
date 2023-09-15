<?php

namespace Lupennat\NestedMany;

use Illuminate\Http\Request;

trait Authorizable
{
    /**
     * Determine if the current user can create new nested resources.
     *
     * @return bool
     */
    public static function authorizedToCreateNested(Request $request)
    {
        return static::authorizedToCreate($request);
    }

    /**
     * Determine if the current user can update the nested given resource.
     *
     * @return bool
     */
    public function authorizedToUpdateNested(Request $request)
    {
        return $this->authorizedToUpdate($request);
    }

    /**
     * Determine if the current user can delete the nested given resource.
     *
     * @return bool
     */
    public function authorizedToDeleteNested(Request $request)
    {
        return $this->authorizedToDelete($request);
    }
}
