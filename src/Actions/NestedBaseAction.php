<?php

namespace Lupennat\NestedMany\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Laravel\Nova\AuthorizedToSee;
use Laravel\Nova\Exceptions\MissingActionHandlerException;
use Laravel\Nova\Fields\FieldCollection;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Makeable;
use Laravel\Nova\Metable;
use Laravel\Nova\Nova;
use Laravel\Nova\ProxiesCanSeeToGate;
use Lupennat\NestedMany\Http\Requests\NestedActionRequest;
use Lupennat\NestedMany\Models\Nested;

abstract class NestedBaseAction implements \JsonSerializable
{
    use AuthorizedToSee;
    use Macroable;
    use Makeable;
    use Metable;
    use ProxiesCanSeeToGate;

    /**
     * The displayable name of the action.
     *
     * @var string
     */
    public $name;

    /**
     * The action's component.
     *
     * @var string
     */
    public $component = 'confirm-action-modal';

    /**
     * Determine where the action redirection should be without confirmation.
     *
     * @var bool
     */
    public $withoutConfirmation = false;

    /**
     * The callback used to authorize running the action.
     *
     * @var (\Closure(\Laravel\Nova\Http\Requests\NovaRequest, mixed):(bool))|null
     */
    public $runCallback;

    /**
     * The text to be used for the action's confirm button.
     *
     * @var string
     */
    public $confirmButtonText = 'Run Action';

    /**
     * The text to be used for the action's cancel button.
     *
     * @var string
     */
    public $cancelButtonText = 'Cancel';

    /**
     * The text to be used for the action's confirmation text.
     *
     * @var string
     */
    public $confirmText = 'Are you sure you want to run this action?';

    /**
     * The size of the modal. Can be "sm", "md", "lg", "xl", "2xl", "3xl", "4xl", "5xl", "6xl", "7xl".
     *
     * @var string
     */
    public $modalSize = '2xl';

    /**
     * The style of the modal. Can be either 'fullscreen' or 'window'.
     *
     * @var string
     */
    public $modalStyle = 'window';

    /**
     * Keep modal opened after submit.
     *
     * @var bool
     */
    public $keepOpened = false;

    /**
     * Is destructive Action.
     */
    public $destructive = false;

    /**
     * new model callback.
     */
    public $newNestedCallback;

    /**
     * Indicates if the action can be run without any models.
     *
     * @var bool
     */
    public $standalone = false;

    /**
     * @param string|null $name
     */
    public function __construct($name = null)
    {
        $this->name = $name;
    }

    /**
     * Determine if the action is executable for the given request.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @return bool
     */
    public function authorizedToRun(Request $request, $model)
    {
        return $this->runCallback ? call_user_func($this->runCallback, $request, $model) : true;
    }

    /**
     * Execute the action for the given request.
     *
     * @return mixed
     *
     * @throws \Laravel\Nova\Exceptions\MissingActionHandlerException|\Throwable
     */
    public function handleRequest(NestedActionRequest $request)
    {
        $fields = $request->resolveFields();

        $dispatcher = new DispatchNestedAction($request, $this, $fields);

        if (method_exists($this, 'dispatchRequestUsing')) {
            $dispatcher->handleUsing($request, function ($request, $response, $fields) {
                return $this->dispatchRequestUsing($request, $response, $fields);
            });
        } else {
            $method = 'handleFor' . Str::plural(class_basename($request->targetModel()));

            $method = method_exists($this, $method) ? $method : 'handle';

            if (!method_exists($this, $method)) {
                throw MissingActionHandlerException::make($this, $method);
            }

            $dispatcher->handleRequest($request, $method);
        }

        $response = $dispatcher->dispatch();

        return $response->results;
    }

    /**
     * Validate the given request.
     *
     * @return array<string, mixed>
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validateFields(NestedActionRequest $request)
    {
        $fields = FieldCollection::make($this->fields($request))
            ->authorized($request)
            ->applyDependsOn($request)
            ->withoutReadonly($request)
            ->withoutUnfillable();

        return Validator::make(
            $request->all(),
            $fields->mapWithKeys(function ($field) use ($request) {
                return $field->getCreationRules($request);
            })->all(),
            [],
            $fields->reject(function ($field) {
                return empty($field->name);
            })->mapWithKeys(function ($field) {
                return [$field->attribute => $field->name];
            })->all()
        )->after(function ($validator) use ($request) {
            $this->afterValidation($request, collect($request->nestedChildren())->map(fn ($model) => $model->getNestedItem()), $validator);
        })->validate();
    }

    /**
     * Set the callback to be run to authorize running the action.
     *
     * @param  \Closure(\Laravel\Nova\Http\Requests\NovaRequest, mixed):bool  $callback
     *
     * @return $this
     */
    public function canRun(\Closure $callback)
    {
        $this->runCallback = $callback;

        return $this;
    }

    /**
     * Get the component name for the action.
     *
     * @return string
     */
    public function component()
    {
        return $this->component;
    }

    /**
     * Get the displayable name of the action.
     *
     * @return string
     */
    public function name()
    {
        return $this->name ?: Nova::humanize($this);
    }

    /**
     * Get the URI key for the action.
     *
     * @return string
     */
    public function uriKey()
    {
        return Str::slug($this->name(), '-', null);
    }

    /**
     * Set the action to execute instantly.
     *
     * @return $this
     */
    public function withConfirmation()
    {
        $this->withoutConfirmation = false;

        return $this;
    }

    /**
     * Set the action to execute instantly.
     *
     * @return $this
     */
    public function withoutConfirmation()
    {
        $this->withoutConfirmation = true;

        return $this;
    }

    /**
     * Set the text for the action's confirmation button.
     *
     * @param string $text
     *
     * @return $this
     */
    public function confirmButtonText($text)
    {
        $this->confirmButtonText = $text;

        return $this;
    }

    /**
     * Set the text for the action's cancel button.
     *
     * @param string $text
     *
     * @return $this
     */
    public function cancelButtonText($text)
    {
        $this->cancelButtonText = $text;

        return $this;
    }

    /**
     * Set the text for the action's confirmation message.
     *
     * @param string $text
     *
     * @return $this
     */
    public function confirmText($text)
    {
        $this->confirmText = $text;

        return $this;
    }

    /**
     * Set the modal to fullscreen style.
     *
     * @return $this
     */
    public function fullscreen()
    {
        $this->modalStyle = 'fullscreen';

        return $this;
    }

    /**
     * Set the size of the modal window.
     *
     * @param string $size
     *
     * @return $this
     */
    public function size($size)
    {
        $this->modalStyle = 'window';
        $this->modalSize = $size;

        return $this;
    }

    /**
     * Keep the modal opened after submit.
     *
     * @return $this
     */
    public function keepOpened($opened = true)
    {
        $this->keepOpened = $opened;

        return $this;
    }

    /**
     * Change Action Destructive.
     *
     * @return $this
     */
    public function destructive($destructive = true)
    {
        $this->destructive = $destructive;

        return $this;
    }

    /**
     * Mark the action as a standalone action.
     *
     * @return $this
     */
    public function standalone()
    {
        $this->standalone = true;

        return $this;
    }

    /**
     * Determine if the action is a standalone action.
     *
     * @return bool
     */
    public function isStandalone()
    {
        return $this->standalone;
    }

    /**
     * Get New Model Callback.
     */
    public function getNewNested(string $relationName = ''): Nested
    {
        /**
         * @var \Lupennat\NestedMany\Http\Requests\NestedActionRequest
         */
        $request = app(NovaRequest::class);

        return $request->newNested($relationName);
    }

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
    }

    /**
     * Get the fields available on the action.
     *
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [];
    }

    /**
     * Prepare the action for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $request = app(NovaRequest::class);

        return array_merge([
            'cancelButtonText' => Nova::__($this->cancelButtonText),
            'component' => $this->component(),
            'confirmButtonText' => Nova::__($this->confirmButtonText),
            'confirmText' => Nova::__($this->confirmText),
            'destructive' => $this->destructive,
            'keepOpened' => $this->keepOpened,
            'name' => $this->name(),
            'uriKey' => $this->uriKey(),
            'fields' => FieldCollection::make($this->fields($request))
                ->filter->authorizedToSee($request)
                ->each->resolveForAction($request)
                ->applyDependsOnWithDefaultValues($request)
                ->values()
                ->all(),
            'standalone' => $this->isStandalone(),
            'modalSize' => $this->modalSize,
            'modalStyle' => $this->modalStyle,
            'withoutConfirmation' => $this->withoutConfirmation,
            'basic' => false,
        ], $this->meta());
    }
}
