![](https://github.com/Lupennat/nova-nested-many/blob/main/demo.gif)

1. [Requirements](#Requirements)
2. [Installation](#Installation)
3. [Usage](#Usage)
4. [HasNestedMany Field](#HasNestedMany-field)
    1. [DependsOn](#depends-on)
    2. [Propagate](#propagate)
    3. [Default Children](#default-children)
    4. [Additional options](#additional-options)
    5. [Hooks](#hooks)
5. [Nestable Resource](#nestable-resource)
    1. [Nestable Title](#nestable-title)
    2. [Nestable Custom Validation](#nestable-custom-validation)
    3. [Nestable Authorization](#nestable-authorization)
    4. [Nestable Actions](#nestable-actions)
        1. [Nestable Basic Actions](#nestable-basic-actions)
        2. [Nestable Soft Delete Action](#Nestable-soft-delete-action)
        3. [Nestable Custom Actions](#nestable-custom-actions)
        4. [Difference With Nova Actions](#difference-with-nova-actions)
        5. [Nested Object](#nested-object)
6. [Recursivity](#recursivity)
7. [Changelog](CHANGELOG.md)
8. [Credits](#credits)

## Requirements

-   `php: ^7.4 | ^8`
-   `laravel/nova: ^4`

## Installation

```
composer require lupennat/nova-nested-many:^2.0
```

| NOVA     | PACKAGE |
| -------- | ------- |
| <4.29.5  | 1.x     |
| >4.29.6  | 2.x     |

## Usage

Register Trait `HasNestedResource` globally on Resources.

```php
namespace App\Nova;

use Laravel\Nova\Resource as NovaResource;
use Lupennat\NestedMany\HasNestedResource;

abstract class Resource extends NovaResource
{
    use HasNestedResource;
}
```

Use `HasManyNested` Field like `HasMany`

```php
namespace App\Nova;

use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Gravatar;
use Laravel\Nova\Fields\Password;
// Add use statement here.
use Lupennat\NestedMany\Fields\HasManyNested;

class User extends Resource
{

    public function fields(Request $request)
    {
        return [
            ID::make()->sortable(),

            Gravatar::make(),

            Text::make('Name')
                ->sortable()
                ->rules('required', 'max:255'),

            Text::make('Email')
                ->sortable()
                ->rules('required', 'email', 'max:254')
                ->creationRules('unique:users,email')
                ->updateRules('unique:users,email,{{resourceId}}'),

            Password::make('Password')
                ->onlyOnForms()
                ->creationRules('required', 'string', 'min:6')
                ->updateRules('nullable', 'string', 'min:6'),

            // Add HasManyNested here.
            HasManyNested::make('Posts'),
        ];
    }

}
```

> HasManyNested is visible by deafult on DetailPage, UpdatePage and CreatePage.
> HasManyNested is not available on IndexPage.

Implements contract `Nestable` and use trait `HasNested` for every related model that will be used with `HasNestedMany`.

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Lupennat\NestedMany\Models\Contracts\Nestable;
use Lupennat\NestedMany\Models\HasNested;

class Post extends Model implements Nestable
{
    use HasNested;

}
```

## HasNestedMany Field

### Depends On

`HasNestedMany` Field support Nova `dependsOn`.

```php
HasNestedMany::make('Posts', Post::class)
    ->dependsOn('name', function(HasNestedMany $field, NovaRequest $novaRequest, FormData $formData) {
        if ($formData->name === 'xxx') {
            $field->show();
        } else {
            $field->hide();
        }
    })
```

### Propagate

`HasNestedMany` Field can propagate parent field value to related resource.

```php
HasNestedMany::make('Posts', Post::class)->propagate(['name'])

// you can also propagate custom key/value to related resource.
HasNestedMany::make('Posts', Post::class)->propagate(['not_a_field' => 'test'])

```

On related resource propagated fields can be retrieved through `getNestedPropagated` method on Request

```php
namespace App\Nova;

use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class Post extends Resource
{

    public function fields(NovaRequest $request)
    {
        return array_filter([
            ID::make(),
            BelongsTo::make(__('User'), 'user', User::class),
            Select::section(__('Section'), 'section')
                ->options(['sport' => 'Sport', 'news' => 'News'])
                ->rules('required'),
            Text::title(__('Title'), 'title')
                ->rules('required'),
            $request->getNestedPropagated('name') === 'xxx' ?
                Text::make(__('Extra Field'), 'extra')->hide() : null
        ]);
    }

}
```

### Default Children

You can use `defaultChildren` method to generate a default set of related resource when children are empty.

> defaultChildren works only on Create Page.

```php
HasNestedMany::make('Posts', Post::class)
    ->defaultChildren([
        ['title' => 'first post', 'section' => 'sport'],
        ['title' => 'second post', 'section' => 'news'],
    ])
```

if you want to overwrite existing children you can use

```php
HasNestedMany::make('Posts', Post::class)
    ->defaultChildren([
        ['title' => 'first post', 'section' => 'sport'],
        ['title' => 'second post', 'section' => 'news'],
    ], true)
```

### Additional options

| function                           | description                                     | default |
| ---------------------------------- | ----------------------------------------------- | ------- |
| `->collapsedChildrenByDefault()`   | Collapse Children on Panel View                 | false   |
| `->useTabs(bool = true)`           | switch display mode to tabs instead of panels   | false   |
| `->active(int = 0)`                | set default active item by index                | 0       |
| `->activeTitle(string)`            | set default active item by title                | null    |
| `->canChangeViewType(bool = true)` | enable switch view type button                  | false   |
| `->hideFields(array<string>)`      | Hide fields on related resource                 | []      |
| `->lock(bool = true)`              | Disable Add/Remove buttons for related resource | false   |
| `->min(int = null)`                | Set Min children                                | null    |
| `->max(int = null)`                | Set Max children                                | null    |

### Hooks

You can specify callbacks before and after `HasNestedMany` fill the database.

```php
namespace App\Nova;

use Illuminate\Http\Request;
use Lupennat\NestedMany\Fields\HasManyNested;
use Laravel\Nova\Http\Requests\NovaRequest;

class User extends Resource
{

    public function fields(Request $request)
    {
        return [
            HasNestedMany::make('Posts', Post::class)
                ->beforeFill(function(\App\Models\User $user, NovaRequest $request) {
                    // do stuff
                })
                ->afterFill(function(\App\Models\User $user, NovaRequest $request) {
                    // do stuff
                })
        ];
    }

}
```

## Nestable Resource

Resource trait `HasNestedResource`, provide new functionality related to Nested.

### Nestable Title

Both panel and tab views, use `nestedTitle` method to retrieve the title of the resource.

> if method not found it will fallback to original nova resource title

```php
namespace App\Nova;

class User extends Resource
{

    public function nestedTitle()
    {
        return $this->resource->name;
    }

}
```

### Nestable Custom Validation

Field validation on nestable resources are automatically managed by `NestedMany`, for each validation error the attribute key is reprocessed and mapped to show feedback to the user in the specific field of the resource generating the error.

When validation is done by adding errors in the validator (e.g., using the afterValidation method), HasManyNested is unable to intercept and remap these errors.
On related resource the correct prefix to prepend to the error attribute can be retrieved through `getNestedValidationKeyPrefix` method on Request.

```php
namespace App\Nova;

class User extends Resource
{
    /**
     * Handle any post-validation processing.
     *
     * @param \Illuminate\Validation\Validator $validator
     *
     * @return void
     */
    protected static function afterValidation(NovaRequest $request, $validator)
    {
        // do logic to detect error
        $isDisposableEmail = true;
        if($isDisposableEmail) {
            $validator
                ->errors()
                ->add(
                    $request->getNestedValidationKeyPrefix() . 'email',
                    'Temporary emails are forbidden.'
                );
        }
    }

```

### Nestable Authorization

Nested Many will use laravel nova resource authorizations for create/update/delete. You can define different policies only for Nested Many within 3 new methods

```php
namespace App\Nova;

class User extends Resource
{

    public static function authorizedToCreateNested(Request $request)
    {
        return true;
    }

    public function authorizedToUpdateNested(Request $request)
    {
        return false;
    }

    public function authorizedToDeleteNested(Request $request)
    {
        return $this->authorizedToDelete($request);
    }

}
```

## Nestable Actions

You can define Nested Actions to manipulate related content through the server.\

> `Nested` Actions can keep the modal open after action is run through the property `$keepOpened` or the method `keepOpened()`.

### Nestable Basic actions

By Default NestedMany has 3 Basic actions `NestedBasicAddAction`, `NestedBasicDeleteAction`, `NestedBasicRestoreAction`, and it is possible to customize them through 3 methods on resource.

```php
namespace App\Nova;

use Lupennat\NestedMany\Actions\Basics\NestedBasicAddAction;
use Lupennat\NestedMany\Actions\Basics\NestedBasicDeleteAction;
use Lupennat\NestedMany\Actions\Basics\NestedBasicRestoreAction;
use Laravel\Nova\Http\Requests\NovaRequest;

class Post extends Resource
{

    /**
     * Get the nested create action on the entity.
     */
    public function nestedAddAction(NovaRequest $request): NestedBasicAddAction
    {
        return new \App\Nova\NestedActions\MyCustomAddActionExtendsBasicAddAction();
    }

    /**
     * Get the nested delete action on the entity.
     */
    public function nestedDeleteAction(NovaRequest $request): NestedBasicDeleteAction
    {
        return parent::nestedDeleteAction($request)->withConfirmation();
    }

    /**
     * Get the nested delete action on the entity.
     */
    public function nestedRestoreAction(NovaRequest $request): NestedBasicRestoreAction
    {
        return parent::nestedRestoreAction($request)->withConfirmation();
    }

}
```

### Nestable Soft Delete Action

NestedDeleteAction automatically support `softDelete` logic (is not a real elouquent softDelete), to enable softdelete/restore logic you need to set `protected $nestedHasSoftDelete = true` on the related model.

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Lupennat\NestedMany\Models\Contracts\Nestable;
use Lupennat\NestedMany\Models\HasNested;

class Post extends Model implements Nestable
{
    use HasNested;

    protected $nestedHasSoftDelete = true;

}
```

### Nestable Custom actions

Nested actions may be generated using the nested-many:action Artisan command. By default, all actions are placed in the app/Nova/NestedActions directory:

```bash
php artisan nested-many:action DuplicatePost
```

You may generate a destructive action by passing the --destructive option:

```bash
php artisan nested-many:action DeleteAllPosts --destructive
```

Nested Actions have a lot in common with Nova Actions, the main difference is that `handle` method should always return a collection of `NestedObject`.

You can generate new `NestedObject` with method `$this->getNewNested()`.

To learn how to define Nova actions, let's look at an example. In this example, we'll define an action that will duplicate a post:

```php
<?php

namespace App\Nova\NestedActions;

use Illuminate\Support\Collection;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Http\Requests\NovaRequest;
use Lupennat\NestedMany\Models\Nested;
use Lupennat\NestedMany\Actions\NestedAction;

class DuplicatePost extends NestedAction
{

    /**
     * Handle any post-validation processing.
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @param \Illuminate\Support\Collection<int,\Lupennat\NestedMany\Models\Nested> $resources
     *
     * @return void
     */
    protected function afterValidation(NovaRequest $request, Collection $resources, $validator)
    {
       // do validation stuff
    }


    /**
     * Perform the action on the given models.
     *
     * @param \Laravel\Nova\Fields\ActionFields $actionFields
     * @param \Illuminate\Support\Collection<int,\Lupennat\NestedMany\Models\Nested> $children
     * @param string|null $selectedUid
     *
     * @return \Illuminate\Support\Collection<int,\Lupennat\NestedMany\Models\Nested>
     */
    public function handle(ActionFields $fields, Collection $children, $selectedUid): Collection
    {
        $selectedNested = $children->where(Nested::UIDFIELD, $selectedUid)->first();

        $children->push(tap($this->getNewNested(), function ($newResource) use ($selectedNested, $fields) {
            foreach ($selectedNested->getAttributes() as $key => $value) {
                if($key !== $selectedNested->getKeyName()) {
                    $newResource->{$key} = $value;
                }
            }
            $newResource->active();
        }));

        return $children;
    }

    /**
     * Get the fields available on the action.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [];
    }
}
```

You can register custom `NestedActions` within your resource throught the method `nestedActions`

```php
namespace App\Nova;

use Lupennat\NestedMany\Actions\Basics\NestedBasicAddAction;

class Post extends Resource
{
    /**
     * Get the actions available on the entity.
     *
     * @return array<\Lupennat\NestedMany\Actions\NestedAction>
     */
    public function nestedActions(NovaRequest $request): array
    {
        return [
            \App\Nova\NestedActions\DuplicatePost::create()
        ];
    }
}
```

### Nested Object

When using `NestedActions`, all `Eloquent` models are wrapped by default in the `Nested` class that extends Laravel `Fluent` class.\
All Model attributes are replicated on `Nested` attributes, that way you can modify attributes values directly to the `Nested` object.

It expose convinient methods to manipulate object without executing queries against the database.

| function                | description                                     | return |
| ----------------------- | ----------------------------------------------- | ------ |
| `->delete()`            | Delete/SoftDelete item                          | self   |
| `->restore()`           | Restore item                                    | self   |
| `->active(bool = true)` | Set item as active                              | self   |
| `->isStored()`          | Detected if item is stored on database          | bool   |
| `->isDeleted()`         | Detect if item is SoftDeleted                   | bool   |
| `->hasSoftDelete()`     | Detect if item support SoftDelete               | bool   |
| `->getKeyName()`        | Get Eloquent Original keyName                   | string |
| `->toModel()`           | Convert current item to Original Eloquent Model | Model  |

> When toModel is called, Nested class apply changes on current object to Original Eloquent Model
> if Mutators are registered, they will be executed.

> Every change executed on Nested Object will not be stored on database until the user click the Create/Update button on the Parent Form Page.

## Recursivity

`HasManyNested` supports recursivity however, it is important to keep in mind that the deletion of a "parent" resource is not recursive to its "children".

Ex. parent resource -> nested resourceA -> nested resourceB

by deleting a nested resource A the nested resources B are not automatically deleted from the database.

You can solve this problem by directly using observers for the delete event on eloquent models.

```php
class Parent extends Model {
    protected static function booted(): void
    {
        static::deleted(function (Parent $model) {
            // to propagate event we need to call ->delete() within the model
            foreach ($model->childrenItemA as $childItemA) {
                $childItemA->delete();
            }
        });
    }

    public childrenItemA() {
        return $this->hasMany(ItemA::class);
    }
}

class ItemA extends Model {

    protected static function booted(): void {
        static::deleted(function (ItemA $model) {
            // we can avoid loop we don't need to propagate event
           $model->childrenItemB()->delete()
        });
    }

    public childrenItemB() {
        return $this->hasMany(ItemB::class);
    }
}

class ItemB extends Model {
}
```

### Nestable Objects with Recursivity

Recursive `HasManyNested` relations, are available within the children collection of `NestedActions`, relations are array of `Nested Object`.
You can generate new relation `NestedObject` with method `$this->getNewNested('relationName')`.

---

# Credits

NestedForm field is based on original [Nova Nested Form](https://github.com/yassilah/laravel-nova-nested-form).