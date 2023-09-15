1. [Requirements](#Requirements)
2. [Installation](#Installation)
3. [Usage](#Usage)
4. [DependsOn](#dependson)
5. [Additional options](#additional-options)
   1. [Prefill](#prefill)
   2. [Custom Heading](#custom-heading)
6. [Credits](#credits)

## Requirements

- `php: ^7.4 | ^8`
- `laravel/nova: ^4`

## Installation

```
composer require lupennat/nested-form
```

## Usage

Simply add a NestedForm into your fields. The first parameter must be an existing NovaResource class and the second parameter (optional) must be an existing HasOneOrMany relationship in your model.

> NestedForm is visible by deafult on DetailPage, UpdatePage and CreatePage.
> NestedForm is not available on IndexPage.

```php
namespace App\Nova;

use Laravel\Nova\Fields\ID;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Gravatar;
use Laravel\Nova\Fields\Password;
// Add use statement here.
use Lupennat\NestedForm\NestedForm;

class User extends Resource
{
    ...
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

            // Add NestedForm here.
            NestedForm::make('Posts'),
        ];
    }
```

## Depends On

NestedForm support Nova `dependsOn`.

```php
NestedForm::make('Posts', Post::class)
    ->dependsOn('name', function(NestedForm $field, NovaRequest $novaRequest, FormData $formData) {
        if ($formData->name === 'xxx') {
            $field->min(1)->max(10);
        }
    })
```

it also support a short version of dependsOn without callback

```php
NestedForm::make('Posts', Post::class)->dependsOn('name')
```

Both syntax will propagate the condition to related resource, you can access propagated content adding `HasNestedForm` trait on your related resource.

```php
use Lupennat\Nova\Resource;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Lupennat\NestedForm\HasNestedForm;

class Posts extends Resource
{

    use HasNestedForm;

    public function fields(Request $request)
    {
        return array_filter([
            ID::make(),
            BelongsTo::make(__('User'), 'user', User::class),
            Select::section(__('Section'), 'section')->options(['sport' => 'Sport', 'news' => 'News'])->rules('required'),
            Text::title(__('Title'), 'title')->rules('required'),
            $this->canShowExtra() ? Text::make(__('Extra Field'), 'extra')->hide() : null
        ]);
    }

    protected function canShowExtra() {
        if (
            $this->getNestedFormParentResource() === User::uriKey() &&
            $this->getNestedFormParentContent('name') === 'xxx'
        ) {
            return true;
        }

        return false;
    }
}
```

## Additional options

| function                       | description                                            | default                   |
| ------------------------------ | ------------------------------------------------------ | ------------------------- |
| `->max(int)`                   | limit number of related resources allowed              | 0                         |
| `->min(int)`                   | minimum number of related resources                    | 0                         |
| `->lock()`                     | disable add and remove related resources               | false                     |
| `->prefill(array,bool)`        | [prefill](#prefill) related resources with values      | [], false                 |
| `->useTabs()`                  | switch display mode to tabs instead of panels          | false                     |
| `->activeTab(int)`             | set default active tab by index                        | 0                         |
| `->activeTabByHeading(string)` | set default active tab by 'heading'                    | null                      |
| `->canDuplicate()`             | enable duplicate resource button                       | false                     |
| `->canRestore()`               | enable soft delete on resource when deleted (only vue) | false                     |
| `->addText(string)`            | text for add button                                    | "add $resourceName"       |
| `->restoreText(string)`        | text for restore button                                | "restore $resourceName"   |
| `->removeText(string)`         | text for remove button                                 | "remove $resourceName"    |
| `->duplicateText(string)`      | text for duplicate button                              | "duplicate $resourceName" |
| `->heading(string, boolean)`   | define [custom heading](#custom-heading)               | [], false                 |
| `->separator(string)`          | define heading separator                               | " . "                     |

### Prefill

You can use `prefill` method to generate a default set of related resource.

> Prefill works only on Create Page

```php
NestedForm::make('Posts', Post::class)
    ->prefill([
        ['title' => 'first post', 'section' => 'sport'],
        ['title' => 'second post', 'section' => 'news'],
    ])
```

You can force prefill to always respect the numbers of prefilled items through a second boolean parameter.

```php
NestedForm::make('Posts', Post::class)
    ->prefill([
        ['title' => 'first post', 'section' => 'sport'],
        ['title' => 'second post', 'section' => 'news'],
    ], true)
```

### Custom Heading

By Default NestedForm Heading is:

- DetailPage `${resourceName} ${keyName}: ${resource[keyName]}`
- UpdatePage exists `${resourceName} ${keyName}: ${resource[keyName]}` new `${index} ${separator} ${resourceName}`
- CreatePage `${index} ${separator} ${resourceName}`

You can specify a Custom Heading using resource fields value, defining attribute names, through `heading` method, the second parameter of the method specify if the heading should be "unique".

When a custom heading is defined, the fields are hidden on the panel, but when the user try to add a new resource a modal with required fields is displayed; if unique is true, NestedForm will check to existing related resource the uniqueness of the fields. Multiple fields value will be concatened by the defined separator.

```php
NestedForm::make('Posts', Post::class)
    ->heading(['section', 'title'], true)
    ->separator(' - ')
```

---

# Credits

NestedForm field is based on original [Nova Nested Form](https://github.com/yassilah/laravel-nova-nested-form).
