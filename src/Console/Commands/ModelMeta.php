<?php

namespace Xin\Laravel\Console\Commands;

use Illuminate\Support\Facades\DB;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;

class ModelMeta extends GeneratorCommand
{
    /**
     * 命令名
     * @var string
     */
    protected $name = 'x:make:model@meta';

    /**
     * 命令描述
     * @var string
     */
    protected $description = 'Create new meta model';


    protected $table = '';

    protected $class = '';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Model';

    /**
     * 命令执行
     *
     * @author Sinute
     * @date   2015-07-07
     * @return void
     */
    public function handle()
    {
        $this->table = $this->getNameInput();
        $this->class = ucfirst(camel_case($this->table));

        $name = $this->qualifyClass($this->class);
        $path = $this->getPath($name);

        // First we will check to see if the class already exists. If it does, we don't want
        // to create the class and overwrite the user's code. So, we will bail out so the
        // code is untouched. Otherwise, we will continue generating this class' files.
        if ($this->alreadyExists($this->class)) {
            $this->error($this->type . ' already exists!');

            return false;
        }

        // Next, we will generate the path to the location where this class' file should get
        // written. Then, we will build the class and make the proper replacements on the
        // stub files so that it gets the correctly formatted namespace and class name.
        $this->makeDirectory($path);

        $this->files->put($path, $this->buildModel($name));

        $this->info($this->type . ' created successfully.');
    }


    protected function buildModel($name)
    {
        $stub = $this->files->get($this->getStub());
        $tableStruct = DB::select('desc ' . config('database.connections.mysql.prefix') . $this->table);

        return $this->replaceTable($stub, $this->table)->replaceFillable($stub, array_pluck($tableStruct, 'Field'))->replaceClass($stub, $name);
    }

    protected function replaceClass($stub, $class)
    {
        $class = str_replace($this->getNamespace($class) . '\\', '', $class);

        return str_replace('{{class}}', $class, $stub);
    }

    protected function replaceTable(&$stub, $table)
    {
        $stub = str_replace(
            '{{table}}', strtolower($table), $stub
        );

        return $this;
    }

    protected function replaceFillable(&$stub, array $fields)
    {
        $fields = array_where($fields, function ($value, $key) {
            return !in_array($value, ['created_at', 'updated_at']);
        });
        $stub = str_replace('{{fillable}}', '\'' . join('\', \'', $fields) . '\'', $stub);

        return $this;
    }


    /**
     * Get the default namespace for the class.
     *
     * @param  string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\Models\Meta';
    }

    /**
     * Get the desired class name from the input.
     *
     * @return string
     */
    protected function getNameInput()
    {
        return trim($this->argument('table'));
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['table', InputArgument::REQUIRED, 'The name of the table'],
        ];
    }

    protected function getStub()
    {
        return __DIR__ . '/../../../stubs/map-model.stub';
    }
}
