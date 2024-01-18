<?php

namespace Lupennat\NestedMany\Models;

/**
 * @property bool $nestedHasSoftDelete
 */
trait HasNested
{
    protected $nestedIsSoftDeleted = false;
    protected $nestedIsDefault = false;
    protected $nestedIsActive = false;
    protected $nestedUid = null;
    protected $nestedRelations = [];

    public function hasNestedSoftDelete(): bool
    {
        return $this->nestedHasSoftDelete ?? false;
    }

    public function nestedSetDefault($default = true): self
    {
        $this->nestedIsDefault = $default;

        return $this;
    }

    public function nestedSetSoftDelete($softDelete = true): self
    {
        $this->nestedIsSoftDeleted = $softDelete;

        return $this;
    }

    public function nestedSetActive($active = true): self
    {
        $this->nestedIsActive = $active;

        return $this;
    }

    public function nestedSetUid($uid): self
    {
        $this->nestedUid = $uid;

        return $this;
    }

    public function nestedSetRelations($relations = []): self
    {
        $this->nestedRelations = $relations;

        return $this;
    }

    public function getNestedUid(): string
    {
        if (!$this->nestedUid) {
            $this->nestedSetUid(uniqid());
        }

        return $this->nestedUid;
    }

    public function isNestedDefault(): bool
    {
        return $this->nestedIsDefault;
    }

    public function isNestedSoftDeleted(): bool
    {
        return $this->nestedIsSoftDeleted;
    }

    public function isNestedActive(): bool
    {
        return $this->nestedIsActive;
    }

    public function getNestedItem(): Nested
    {
        return new Nested($this, array_reduce(array_keys($this->nestedRelations), function ($carry, $key) {
            $carry[$key] = array_map(function ($model) {
                return $model->getNestedItem();
            }, $this->nestedRelations[$key]);
            return $carry;
        }, []), $this->nestedUid);
    }
}
