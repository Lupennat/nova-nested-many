<?php

namespace Lupennat\NestedMany\Fields;

use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\MorphTo;
use Laravel\Nova\Http\Controllers\ResourceDestroyController;
use Laravel\Nova\Http\Controllers\ResourceStoreController;
use Laravel\Nova\Http\Controllers\ResourceUpdateController;
use Laravel\Nova\Http\Requests\CreateResourceRequest;
use Laravel\Nova\Http\Requests\DeleteResourceRequest;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Http\Requests\UpdateResourceRequest;
use Laravel\Nova\Util;
use Lupennat\NestedMany\NestedChildrenHelper;

trait NestedStorable
{
    /**
     * Before fill Callback.
     *
     * @var (\Closure(\Illuminate\Database\Eloquent\Model, Laravel\Nova\Http\Requests\NovaRequest):(void))
     */
    public $beforeFillCallback;

    /**
     * After fill Callback.
     *
     * @var (\Closure(\Illuminate\Database\Eloquent\Model, Laravel\Nova\Http\Requests\NovaRequest):(void))
     */
    public $afterFillCallback;

    /**
     * Before Fill Hook.
     *
     * @param (\Closure(\Illuminate\Database\Eloquent\Model, Laravel\Nova\Http\Requests\NovaRequest):(void)) $callback
     *
     * @return $this
     */
    public function beforeFill($callback)
    {
        $this->beforeFillCallback = $callback;

        return $this;
    }

    /**
     * After Fill Hook.
     *
     * @param (\Closure(\Illuminate\Database\Eloquent\Model, Laravel\Nova\Http\Requests\NovaRequest):(void)) $callback
     *
     * @return $this
     */
    public function afterFill($callback)
    {
        $this->afterFillCallback = $callback;

        return $this;
    }

    /**
     * Get the validation attribute names for the field.
     *
     * @return array<string, string>
     */
    public function getValidationAttributeNames(NovaRequest $request)
    {
        $resourceClass = $this->resourceClass;
        $resource = new $resourceClass($resourceClass::newModel());

        $attributeNames = parent::getValidationAttributeNames($request);

        $childrenCount = NestedChildrenHelper::countNestedChildren($request, $this->attribute, $this->resourceClass);

        if ($childrenCount) {
            $attributeNames = array_merge($attributeNames, $resource
                ->availableFields($request)
                ->reject(function ($field) {
                    return empty($field->name);
                })
                ->reduce(function ($carry, $field) use ($childrenCount, $request) {
                    foreach (range(0, $childrenCount - 1) as $i) {
                        if ($field instanceof HasManyNested) {
                            foreach ($field->getValidationAttributeNamesFromParent($request, $this->resourceName, $this->attribute, $i) as $attribute => $name) {
                                $carry["{$this->attribute}.{$i}.{$attribute}"] = $name;
                            }
                        } else {
                            $carry["{$this->attribute}.{$i}.{$field->attribute}"] = $field->name;
                        }
                    }

                    return $carry;
                }, []));
        }

        return $attributeNames;
    }

    /**
     * Get the creation rules for this field.
     *
     * @return array<string, array<int, string|\Illuminate\Validation\Rule|\Illuminate\Contracts\Validation\Rule|callable>>
     */
    public function getCreationRules(NovaRequest $request)
    {
        return array_merge_recursive(parent::getCreationRules($request), $this->getAvailableValidationRules($request));
    }

    /**
     * Get the update rules for this field.
     *
     * @return array<string, array<int, string|\Illuminate\Validation\Rule|\Illuminate\Contracts\Validation\Rule|callable>>
     */
    public function getUpdateRules(NovaRequest $request)
    {
        return array_merge_recursive(parent::getUpdateRules($request), $this->getAvailableValidationRules($request));
    }

    /**
     * Get the available rules for this field.
     *
     * @return array<string, array<int, string|\Illuminate\Validation\Rule|\Illuminate\Contracts\Validation\Rule|callable>>
     */
    protected function getAvailableValidationRules(NovaRequest $request)
    {
        $rules = [];

        $resourceClass = $this->resourceClass;

        $children = NestedChildrenHelper::getNestedChildrenModelAttributes($request, $this->attribute, $resourceClass);

        foreach ($children as $index => $child) {
            $resource = new $resourceClass($child['model']);
            $rules = array_merge_recursive(
                $rules,
                $child['model']->exists ?
                $this->getResourceUpdateRules($index, $request, $resource) :
                $this->getResourceCreationRules($index, $request, $resource)
            );
        }

        return $rules;
    }

    /**
     * Get the creation rules for this field.
     *
     * @param \Laravel\Nova\Resource $resource
     *
     * @return array<string, array<int, string|\Illuminate\Validation\Rule|\Illuminate\Contracts\Validation\Rule|callable>>
     */
    public function getResourceCreationRules(int $index, NovaRequest $request, $resource)
    {
        $replacements = Util::dependentRules($this->attribute);

        return $resource->creationFields($request)
            ->reject($this->rejectRecursiveRelatedResourceFields($request))
            ->applyDependsOn($request)
            ->mapWithKeys(function ($field) use ($request, $index) {
                if ($field instanceof HasManyNested) {
                    return $field->getCreationRulesFromParent($request, $this->resourceName, $this->attribute, $index);
                }

                return $field->getCreationRules($request);
            })
            ->mapWithKeys(function ($rules, $attribute) use ($replacements, $index) {
                if ($this->nullable === true) {
                    array_push($field, 'sometimes');
                }

                return ["{$this->attribute}.{$index}.{$attribute}" => collect($rules)->transform(function ($rule) use ($replacements) {
                    if (empty($replacements)) {
                        return $rule;
                    }

                    return is_string($rule)
                            ? str_replace(array_keys($replacements), array_values($replacements), $rule)
                            : $rule;
                })->all()];
            })
            ->prepend(['array', $this->nullable === true ? 'nullable' : 'required'], $this->attribute)
            ->all();
    }

    /**
     * Get the update rules for this resource fields.
     *
     * @param \Laravel\Nova\Resource $resource
     *
     * @return array<string, array<int, string|\Illuminate\Validation\Rule|\Illuminate\Contracts\Validation\Rule|callable>>
     */
    public function getResourceUpdateRules(int $index, NovaRequest $request, $resource)
    {
        $replacements = collect([
            '{{resourceId}}' => str_replace(['\'', '"', ',', '\\'], '', $resource->model()->getKey() ?? ''),
        ])->merge(
            Util::dependentRules($this->attribute),
        )->filter()->all();

        return $resource->updateFields($request)
            ->reject($this->rejectRecursiveRelatedResourceFields($request))
            ->applyDependsOn($request)
            ->mapWithKeys(function ($field) use ($request, $index) {
                if ($field instanceof HasManyNested) {
                    return $field->getUpdateRulesFromParent($request, $this->resourceName, $this->attribute, $index);
                }

                return $field->getUpdateRules($request);
            })
            ->mapWithKeys(function ($field, $attribute) use ($replacements, $index) {
                if ($this->nullable === true) {
                    array_push($field, 'sometimes');
                }

                return ["{$this->attribute}.{$index}.{$attribute}" => collect($field)->transform(function ($rule) use ($replacements) {
                    if (empty($replacements)) {
                        return $rule;
                    }

                    return is_string($rule)
                            ? str_replace(array_keys($replacements), array_values($replacements), $rule)
                            : $rule;
                })->all()];
            })
            ->prepend(['array', $this->nullable === true ? 'nullable' : 'required'], $this->attribute)
            ->all();
    }

    /**
     * Reject recursive related resource fields.
     *
     * @return \Closure
     */
    protected function rejectRecursiveRelatedResourceFields(NovaRequest $request)
    {
        return function ($field) use ($request) {
            if (
                (($field instanceof BelongsTo || $field instanceof BelongsToMany) && $field->resourceName === $request->route('resource'))
                || ($field instanceof MorphTo && collect($field->morphToTypes)->pluck('value')->contains($request->route('resource')))
            ) {
                return true;
            }

            return false;
        };
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     *
     * @param object      $model
     * @param string      $attribute
     * @param string|null $requestAttribute
     *
     * @return (\Closure():(void))|null
     */
    public function fillInto(NovaRequest $request, $model, $attribute, $requestAttribute = null)
    {
        if (!$model->exists) {
            $model::created(function ($model) use ($request, $requestAttribute, $attribute) {
                $this->fillInto($request, $model, $attribute, $requestAttribute);
            });
        } else {
            if (is_callable($this->beforeFillCallback)) {
                call_user_func($this->beforeFillCallback, $model, $request);
            }

            $resourceClass = $this->resourceClass;

            $children = NestedChildrenHelper::getNestedChildrenModelAttributes($request, $this->attribute, $resourceClass);

            $keyName = $this->resourceClass::newModel()->getKeyName();

            $keys = collect($children)->map(function ($child) {
                return $child['model']->getKey();
            })
                ->reject(fn ($key) => !$key)
                ->values();

            $childrenToDelete = $model->{$this->relationshipName()}()->whereNotIn($keyName, $keys)->pluck($keyName)->toArray();

            $viaResource = $request->route('resource');
            $viaResourceId = $request->route('resourceId');

            $relatedFields = (new $resourceClass())
                ->availableFields($request)
                ->filter($this->rejectRecursiveRelatedResourceFields($request))
                ->values();

            $viaRelationship = $this->attribute;

            $request->route()->setParameter('resource', $this->resourceName);
            $request->route()->forgetParameter('resourceId');

            $newRequest = NovaRequest::createFrom($request);

            $this->deleteChildren($newRequest, $childrenToDelete);

            foreach ($children as $index => $child) {
                foreach ($relatedFields as $field) {
                    $child['attributes'][$field->attribute] = $model->getKey();
                }

                if ($child['model']->exists) {
                    $this->updateChild($newRequest, $model, $child, $index, $viaResource, $viaRelationship);
                } else {
                    $this->createChild($newRequest, $model, $child, $index, $viaResource, $viaRelationship);
                }
            }

            $request->route()->setParameter('resource', $viaResource);
            $request->route()->setParameter('resourceId', $viaResourceId);

            if (is_callable($this->afterFillCallback)) {
                call_user_func($this->afterFillCallback, $model, $request);
            }
        }
    }

    /**
     * Create the child sent through the request.
     */
    protected function createChild(NovaRequest $request, $model, $child, $index, $viaResource, $viaRelationship)
    {
        $createRequest = CreateResourceRequest::createFrom(
            $request
                ->replace([
                    'viaResource' => $viaResource,
                    'viaResourceId' => $model->getKey(),
                    'viaRelationship' => $viaRelationship,
                    'nestedPropagated' => $request->nestedPropagated,
                ])
                ->merge($child['attributes'])
        );

        $createRequest['editMode'] = 'create';

        $createRequest->files->replace($request->file("{$this->attribute}.{$index}", []));

        return (new ResourceStoreController())->__invoke($createRequest);
    }

    /**
     * Update the child sent through the request.
     */
    protected function updateChild(NovaRequest $request, $model, $child, $index, $viaResource, $viaRelationship)
    {
        $updateRequest = UpdateResourceRequest::createFrom(
            $request
                ->replace([
                    'viaResource' => $viaResource,
                    'viaResourceId' => $model->getKey(),
                    'viaRelationship' => $viaRelationship,
                    'nestedPropagated' => $request->nestedPropagated,
                ])
                ->merge($child['attributes'])
        );

        $updateRequest['editMode'] = 'update';

        $updateRequest->route()->setParameter('resourceId', $child['model']->getKey());

        $updateRequest->files->replace($request->file("{$this->attribute}.{$index}", []));

        return (new ResourceUpdateController())->__invoke($updateRequest);
    }

    /**
     * Delete the children not sent through the request.
     */
    protected function deleteChildren(NovaRequest $request, $children)
    {
        if (count($children)) {
            $deleteRequest = DeleteResourceRequest::createFrom(
                $request->replace([
                    'viaResource' => null,
                    'viaResourceId' => null,
                    'viaRelationship' => null,
                    'resources' => $children,
                    'nestedPropagated' => $request->nestedPropagated,
                ])
            );

            return (new ResourceDestroyController())->__invoke($deleteRequest);
        }
    }
}
