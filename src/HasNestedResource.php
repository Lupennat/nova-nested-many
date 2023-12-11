<?php

namespace Lupennat\NestedMany;

use Illuminate\Support\Collection;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\MorphTo;
use Laravel\Nova\Http\Requests\NovaRequest;
use Lupennat\NestedMany\Actions\Basics\NestedBasicAction;
use Lupennat\NestedMany\Actions\Basics\NestedBasicAddAction;
use Lupennat\NestedMany\Actions\Basics\NestedBasicDeleteAction;
use Lupennat\NestedMany\Actions\Basics\NestedBasicRestoreAction;
use Lupennat\NestedMany\Fields\HasManyNested;

/**
 * @template TValidationRule of \Stringable|string|\Illuminate\Contracts\Validation\Rule|\Illuminate\Contracts\Validation\InvokableRule|callable>|\Stringable|string|((callable(string, mixed, \Closure):(void))
 *
 * @method string nestedTitle()
 */
trait HasNestedResource
{
    use Authorizable;

    /**
     * Get the actions available on the entity.
     *
     * @return array<\Lupennat\NestedMany\Actions\NestedAction>
     */
    public function nestedActions(NovaRequest $request): array
    {
        return [];
    }

    /**
     * Get the nested create action on the entity.
     */
    public function nestedAddAction(NovaRequest $request): NestedBasicAddAction
    {
        return new NestedBasicAddAction('Add ' . $this::singularLabel());
    }

    /**
     * Get the nested delete action on the entity.
     */
    public function nestedDeleteAction(NovaRequest $request): NestedBasicDeleteAction
    {
        return tap(new NestedBasicDeleteAction('Remove ' . $this::singularLabel()), function (NestedBasicDeleteAction $action) {
            if (!$this->resource->hasNestedSoftDelete()) {
                $action->withConfirmation();
            }
        });
    }

    /**
     * Get the nested delete action on the entity.
     */
    public function nestedRestoreAction(NovaRequest $request): NestedBasicRestoreAction
    {
        return new NestedBasicRestoreAction('Restore ' . $this::singularLabel());
    }

    /**
     * Get the actions that are available for the given request.
     *
     * @return \Illuminate\Support\Collection<int,\Lupennat\NestedMany\Actions\NestedAction>
     */
    public function availableNestedActions(NovaRequest $request)
    {
        $resource = $this->resource;

        if (method_exists($resource, 'getKey')) {
            $request->mergeIfMissing(array_filter([
                'resourceId' => $resource->getKey(),
            ]));
        }

        $actions = $this->resolveNestedActions($request)
            ->reject(fn ($action) => $action instanceof NestedBasicAction)
            ->filter->authorizedToSee($request);

        if (optional($resource)->exists === true) {
            return $actions->filter->authorizedToRun($request, $resource)->values();
        }

        return $actions->values();
    }

    /**
     * Get the actions for the given request.
     *
     * @return \Illuminate\Support\Collection<int,\Lupennat\NestedMany\Actions\NestedAction>
     */
    public function resolveNestedActions(NovaRequest $request)
    {
        return Collection::make(
            $this->filter($this->nestedActions($request))
        );
    }

    /**
     * Get the add action available for the given request.
     */
    public function availableNestedAddAction(NovaRequest $request): NestedBasicAddAction
    {
        $resource = $this->resource;

        if (method_exists($resource, 'getKey')) {
            $request->mergeIfMissing(array_filter([
                'resourceId' => $resource->getKey(),
            ]));
        }

        $action = $this->resolveNestedAddAction($request);

        if (!$action->authorizedToSee($request)) {
            return null;
        }

        if (optional($resource)->exists === true) {
            return $action->authorizedToRun($request, $resource);
        }

        return $action;
    }

    /**
     * Get the add Action for the given request.
     */
    public function resolveNestedAddAction(NovaRequest $request): NestedBasicAddAction
    {
        return $this->nestedAddAction($request);
    }

    /**
     * Get the delete action available for the given request.
     */
    public function availableNestedDeleteAction(NovaRequest $request): NestedBasicDeleteAction
    {
        $resource = $this->resource;

        if (method_exists($resource, 'getKey')) {
            $request->mergeIfMissing(array_filter([
                'resourceId' => $resource->getKey(),
            ]));
        }

        $action = $this->resolveNestedDeleteAction($request);

        if (!$action->authorizedToSee($request)) {
            return null;
        }

        if (optional($resource)->exists === true) {
            return $action->authorizedToRun($request, $resource);
        }

        return $action;
    }

    /**
     * Get the delete Action for the given request.
     */
    public function resolveNestedDeleteAction(NovaRequest $request): NestedBasicDeleteAction
    {
        return $this->nestedDeleteAction($request);
    }

    /**
     * Get the restore action available for the given request.
     */
    public function availableNestedRestoreAction(NovaRequest $request): NestedBasicRestoreAction
    {
        $resource = $this->resource;

        if (method_exists($resource, 'getKey')) {
            $request->mergeIfMissing(array_filter([
                'resourceId' => $resource->getKey(),
            ]));
        }

        $action = $this->resolveNestedRestoreAction($request);

        if (!$action->authorizedToSee($request)) {
            return null;
        }

        if (optional($resource)->exists === true) {
            return $action->authorizedToRun($request, $resource);
        }

        return $action;
    }

    /**
     * Get the restore Action for the given request.
     */
    public function resolveNestedRestoreAction(NovaRequest $request): NestedBasicRestoreAction
    {
        return $this->nestedRestoreAction($request);
    }

    /**
     * Map field attributes to field names.
     *
     * @param \Laravel\Nova\Resource|null $resource
     *
     * @return \Illuminate\Support\Collection<string, string>
     */
    private static function attributeNestedNamesForFields(NovaRequest $request, $resource = null)
    {
        $resource = $resource ?: self::newResource();

        return $resource
            ->availableFields($request)
            ->reject(function ($field) {
                return empty($field->name);
            })
            ->mapWithKeys(function ($field) use ($request) {
                return $field->getValidationAttributeNames($request);
            });
    }

    /**
     * Prepare the resource for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function serializeForNestedDetail(NovaRequest $request)
    {
        return array_merge(
            $this->serializeWithId(
                $this->rejectNestedRelatedField(
                    $this->detailFieldsWithinPanels($request, $this),
                    $request
                )->values()
            ),
            [
                'primaryKey' => $this->resource->exists ? $this->resource->getKey() ?? null : null,
                'nestedUid' => $this->resource->getNestedUid(),
                'isNestedDefault' => false,
                'isNestedSoftDeleted' => false,
                'isNestedActive' => $this->resource->isNestedActive(),
                'title' => method_exists($this, 'nestedTitle') ? $this->nestedTitle($request) : $this->title(),
            ]
        );
    }

    /**
     * Prepare the resource for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function serializeForNestedUpdate(NovaRequest $request, $index)
    {
        request()->setMethod('GET');

        return array_merge(
            $this->serializeWithId(
                $this->adaptFieldForNestedMany(
                    $this->updateFieldsWithinPanels($request, $this)->applyDependsOnWithDefaultValues($request),
                    $request,
                    $index
                )->values()
            ),
            [
                'primaryKey' => $this->resource->exists ? $this->resource->getKey() ?? null : null,
                'nestedUid' => $this->resource->getNestedUid(),
                'isNestedDefault' => $this->resource->isNestedDefault(),
                'isNestedSoftDeleted' => $this->resource->isNestedSoftDeleted(),
                'isNestedActive' => $this->resource->isNestedActive(),
                'title' => method_exists($this, 'nestedTitle') ? $this->nestedTitle($request) : $this->title(),
                'authorizedToUpdateNested' => $this->authorizedToUpdateNested($request),
                'authorizedToDeleteNested' => $this->authorizedToDeleteNested($request),
            ]
        );
    }

    /**
     * Prepare the resource for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function serializeForNestedCreate(NovaRequest $request, $index)
    {
        request()->setMethod('GET');

        return array_merge(
            $this->serializeWithId(
                $this->adaptFieldForNestedMany(
                    $this->creationFieldsWithinPanels($request, $this)->applyDependsOnWithDefaultValues($request),
                    $request,
                    $index,
                    false
                )->values()
            ),
            [
                'primaryKey' => $this->resource->exists ? $this->resource->getKey() ?? null : null,
                'nestedUid' => $this->resource->getNestedUid(),
                'title' => method_exists($this, 'nestedTitle') ? $this->nestedTitle() : $this->title(),
                // when user can create resource it should always be able to remove and edit before store on DB
                'isNestedDefault' => $this->resource->isNestedDefault(),
                'isNestedSoftDeleted' => $this->resource->isNestedSoftDeleted(),
                'isNestedActive' => $this->resource->isNestedActive(),
                'authorizedToUpdateNested' => true,
                'authorizedToDeleteNested' => true,
            ]
        );
    }

    /**
     * Resolve the creation fields and assign them to their associated panel.
     *
     * @return \Laravel\Nova\Fields\FieldCollection<int, \Laravel\Nova\Fields\Field>
     */
    public function nestedCreationFieldsWithinPanels(NovaRequest $request, array $mandatoryFields)
    {
        return $this->filterMandatoryFields($this->creationFieldsWithinPanels($request, $this), $request, $mandatoryFields);
    }

    /**
     * Filter related field.
     *
     * @param \Laravel\Nova\Fields\FieldCollection<int, \Laravel\Nova\Fields\Field> $fields
     *
     * @return \Laravel\Nova\Fields\FieldCollection<int, \Laravel\Nova\Fields\Field>
     */
    protected function filterMandatoryFields($fields, NovaRequest $request, array $mandatoryFields)
    {
        $fields = $this->rejectNestedRelatedField($fields, $request);

        if ($mandatoryFields) {
            return $fields->reject(function ($field) use ($mandatoryFields) {
                return !in_array($field->attribute, $mandatoryFields);
            })
                ->values();
        }

        return $fields;
    }

    /**
     * Filter related field.
     *
     * @param \Laravel\Nova\Fields\FieldCollection<int, \Laravel\Nova\Fields\Field> $fields
     *
     * @return \Laravel\Nova\Fields\FieldCollection<int, \Laravel\Nova\Fields\Field>
     */
    protected function rejectNestedRelatedField($fields, NovaRequest $request)
    {
        return $fields->reject(function ($field) use ($request) {
            if (
                (($field instanceof BelongsTo || $field instanceof BelongsToMany) && $field->resourceName === $request->viaResource)
                || ($field instanceof MorphTo && collect($field->morphToTypes)->pluck('value')->contains($request->viaResource))
            ) {
                return true;
            }

            return false;
        });
    }

    /**
     * Filter related field.
     *
     * @param \Laravel\Nova\Fields\FieldCollection<int, \Laravel\Nova\Fields\Field> $fields
     *
     * @return \Laravel\Nova\Fields\FieldCollection<int, \Laravel\Nova\Fields\Field>
     */
    protected function adaptFieldForNestedMany($fields, NovaRequest $request, $index, $checkCanUpdate = true)
    {
        $fields = $this->rejectNestedRelatedField($fields, $request);

        return $fields->map(function ($field) use ($checkCanUpdate, $request, $index) {
            if ($field instanceof HasManyNested) {
                $field->generateResourcesFromParent($request, $index);
            } else {
                if (($checkCanUpdate && !$this->authorizedToUpdateNested($request)) || $this->resource->isNestedSoftDeleted()) {
                    return $field->readonly(true);
                }
            }

            return $field;
        });
    }
}
