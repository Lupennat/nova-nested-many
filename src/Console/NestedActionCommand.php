<?php

namespace Lupennat\NestedMany\Console;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class NestedActionCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'nested-many:action';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new nested action class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Action';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {

        if ($this->option('destructive')) {
            return $this->resolveStubPath("/stubs/destructive-action.stub");
        }

        return $this->resolveStubPath("/stubs/action.stub");
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Nova\NestedActions';
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['destructive', null, InputOption::VALUE_NONE, 'Indicate that the action deletes / destroys resources']
        ];
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        return __DIR__ . $stub;
    }
}
