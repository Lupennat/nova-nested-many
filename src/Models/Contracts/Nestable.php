<?php

namespace Lupennat\NestedMany\Models\Contracts;

use Lupennat\NestedMany\Models\Nested;

interface Nestable
{
    public function hasNestedSoftDelete(): bool;

    public function nestedSetDefault($default = true): self;

    public function nestedSetSoftDelete($softDelete = true): self;

    public function nestedSetActive($active = true): self;

    public function nestedSetUid($uid): self;

    public function getNestedUid(): string;

    public function isNestedDefault(): bool;

    public function isNestedSoftDeleted(): bool;

    public function isNestedActive(): bool;

    public function getNestedItem(): Nested;
}
