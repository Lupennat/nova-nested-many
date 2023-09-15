<?php

namespace Lupennat\NestedMany\Fields;

use Illuminate\Support\Arr;
use Illuminate\Support\LazyCollection;
use Laravel\Nova\Contracts\RelatableField;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\FormData;
use Laravel\Nova\Fields\MorphTo;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Util;

trait NestedPropagable
{
    /**
     * List of propagate dependencies.
     *
     * @var array<string>
     */
    public array $propagateDependencies = [];

    /**
     * List of propagate attributes value.
     *
     * @var array<string,mixed>|null
     */
    public $propagated = null;

    /**
     * Register propagate a field.
     *
     * @param string|\Laravel\Nova\Fields\Field|array<int, string|\Laravel\Nova\Fields\Field> $attributes
     *
     * @return $this
     */
    public function propagate($attributes)
    {
        $this->propagateDependencies = collect(Arr::wrap($attributes))->map(function ($item) {
            if ($item instanceof MorphTo) {
                return [$item->attribute, "{$item->attribute}_type"];
            }

            return $item instanceof Field ? $item->attribute : $item;
        })->flatten()->all();

        // resolve propagated when edit or create
        $this->dependsOn($this->propagateDependencies, function ($field, NovaRequest $novaRequest) {
            $field->applyPropagate($novaRequest);
        });

        return $this;
    }

    /**
     * Apply depends on logic.
     *
     * @return $this
     */
    public function applyPropagate(NovaRequest $request)
    {
        $propagated = FormData::onlyFrom($request, $this->propagateDependencies)->toArray();
        $this->propagated = count($propagated) ? $propagated : null;
    }

    /**
     * Resolve the field's value for display.
     *
     * @param mixed       $resource
     * @param string|null $attribute
     *
     * @return void
     */
    public function resolveForDisplay($resource, $attribute = null)
    {
        $request = app(NovaRequest::class);

        if (!$request['nestedResolving']) {
            $resourceClass = $request->resource();
            $resourceResolved = new $resourceClass($resource);
            $request['nestedResolving'] = true;
            $fields = $resourceResolved->detailFieldsWithinPanels($request, $resourceResolved);

            $payloads = new LazyCollection(function () use ($fields, $request) {
                foreach ($fields as $field) {
                    $key = $field instanceof RelatableField ? $field->relationshipName() : $field->attribute;

                    if ($field instanceof MorphTo) {
                        yield "{$key}_type" => $field->morphToType;
                    }

                    yield $key => Util::hydrate($field->resolveDependentValue($request));
                }
            });
            $this->applyPropagate(NovaRequest::createFrom($request)->mergeIfMissing($payloads->all()));
            unset($request['nestedResolving']);
        }

        return parent::resolveForDisplay($resource, $attribute);
    }
}
