<?php

namespace Lupennat\NestedMany\Models;

use Illuminate\Support\Fluent;
use Lupennat\NestedMany\Models\Contracts\Nestable;

/**
 * @template TKey of array-key
 * @template TValue
 */
class Nested extends Fluent
{
    public const UIDFIELD = 'nestedUid';

    /**
     * Is New Nested.
     */
    protected bool $isNew = false;

    /**
     * Original Eloquent Model.
     */
    protected Nestable $eloquentModel;

    /**
     * Nested Relations keys
     */
    protected $nestedRelationKeys = [];

    /**
     * Create a new Nested instance.
     */
    public function __construct(Nestable $eloquentModel, $nestedRelations, $isNew = false)
    {
        $this->eloquentModel = $eloquentModel;
        $this->isNew = $isNew;
        $this->nestedRelationKeys = array_keys($nestedRelations);

        parent::__construct(array_merge($eloquentModel->attributesToArray(), $nestedRelations, [static::UIDFIELD => $eloquentModel->getNestedUid()]));
    }

    /**
     * Mark Nested as deleted.
     */
    public function delete(): self
    {
        $this->eloquentModel->nestedSetSoftDelete(true);

        return $this;
    }

    /**
     * Mark Nested as restored.
     */
    public function restore(): self
    {
        $this->eloquentModel->nestedSetSoftDelete(false);

        return $this;
    }

    /**
     * Set Active status for Nested.
     */
    public function active($active = true): self
    {
        $this->eloquentModel->nestedSetActive($active);

        return $this;
    }

    /**
     * Detect if Nested is Default.
     */
    public function isDefault(): bool
    {
        return $this->eloquentModel->isNestedDefault();
    }

    /**
     * Detect if Nested is Stored.
     */
    public function isStored(): bool
    {
        return $this->eloquentModel->exists;
    }

    /**
     * Detect if Nested is New.
     */
    public function isNew(): bool
    {
        return $this->isNew;
    }

    /**
     * Detect if Nested is Deleted.
     */
    public function isDeleted(): bool
    {
        return $this->eloquentModel->isNestedSoftDeleted();
    }

    /**
     * Detect if Nested is Active.
     */
    public function isActive(): bool
    {
        return $this->eloquentModel->isNestedActive();
    }

    /**
     * Detect if Nested support Soft Delete.
     */
    public function hasSoftDelete(): bool
    {
        return $this->eloquentModel->hasNestedSoftDelete();
    }

    /**
     * Get Eloquent Key Name
     */
    public function getKeyName(): string
    {
        return $this->eloquentModel->getKeyName();
    }

    /**
     * Get the attributes from the fluent instance.
     *
     * @return array<TKey, TValue>
     */
    public function getAttributes()
    {
        return array_filter(parent::getAttributes(), fn ($key) => $key !== static::UIDFIELD && !in_array($key, $this->nestedRelationKeys), ARRAY_FILTER_USE_KEY);
    }


    protected function getRelations()
    {
        return array_filter(parent::getAttributes(), fn ($key) => in_array($key, $this->nestedRelationKeys), ARRAY_FILTER_USE_KEY);
    }

    public function toModel()
    {
        foreach ($this->getAttributes() as $key => $value) {
            $this->eloquentModel->{$key} = $value;
        }

        foreach ($this->getRelations() as $key => $values) {
            $this->eloquentModel->setRelation(
                $key,
                array_map(fn ($nested) => $nested->toModel(), array_filter($values, function ($nested) {
                    return !$nested->isDeleted() || $nested->hasSoftDelete();
                }))
            );
        }

        return $this->eloquentModel;
    }
}
