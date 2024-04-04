## v2.2.3 v1.6.7

### Bug Fix

-   default children when creating resource

## v2.2.2

### New

-   fix action selector

## v2.2.1 v1.7.1

### New

-   fix nestedValidationKeyPrefix

## v2.2.0 v1.7.0

### New

-   exposed method `getNestedValidationKeyPrefix` on novarequest

## v2.1.2 v1.6.6

### Bug Fix

-   default children do not load all nested relations
-   improve recursivity
-   nested actions do not write on database

## v2.1.1 v1.6.5

### Bug Fix

-   get defaults do not abort 403 when create is forbidden

### New

-   propagate support custom key/value propagation

## v2.1.0 v1.6.4

### New

-   support recursive nested

## v2.0.0

-   support nova > 4.29.5

## v1.6.3

### Bug Fix

-   reset errors when action triggered
-   locked to nova <4.29.5

## v1.6.1

### New

-   beforeFill and afterFill hook added parameter NovaRequest

## v1.6.0

### New

-   beforeFill hook
-   afterFill hook

## v1.5.4

### Bug Fix

-   GET method on request when serializing fields ([#10](https://github.com/Lupennat/nova-nested-many/issues/10))

## v1.5.3

### Bug Fix

-   Multiple HasManyNested on resource ([#13](https://github.com/Lupennat/nova-nested-many/issues/13))

## v1.5.2

### Bug Fix

-   HasManyNested field hidden on Index Page by default

## v1.5.1

### Bug Fix

-   Laravel Nova InlineFormData replaced with custom NestedFormData ([#10](https://github.com/Lupennat/nova-nested-many/issues/10))

## v1.5.0

### New

-   default children overwrite option
-   min option
-   max option

## v1.4.1

### Bug Fix

-   readonly fields do not work when new child is Nested SoftDeleted.

## v1.4.0

### Bug Fix

-   nestedUid is not preserved across requests

## v1.3.0

### Bug Fix

-   reourceId is not propagated to child fields

## v1.2.0

### Bug Fix

-   Field Help doesn't work

## v1.1.0

### Bug Fix

-   fill field

## v1.0.0

-   first releas
