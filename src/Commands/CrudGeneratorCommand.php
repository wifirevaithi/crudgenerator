<?php

namespace Intrithm\CrudGenerator\Commands;

use Illuminate\Console\Command;
use Str;
use File;

class CrudGeneratorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'intrithm:generatecrud { name : Class (Singularr) , e.g User}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Crud';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $name = $this->argument('name');
        $this->migrate($name);
        $this->model(ucfirst($name));
        $this->controller($name);
        $this->views($name,'index');
        $this->views($name,'create');
        $this->views($name,'edit');
        $this->generateRoute($name);
    }

    protected function getStub($type){
        return file_get_contents(__DIR__ . '/../stubs/'.$type.'.stub');
    }
    protected function getViewStub($type){
        return file_get_contents(__DIR__ . '/../stubs/views/'.$type.'.blade.stub');
    }


    protected function getMigrationPath($name)
    {
        $name = str_replace($this->laravel->getNamespace(), '', $name);
        $datePrefix = date('Y_m_d_His');

        return database_path('/migrations/') . $datePrefix . '_create_' . $name . '_table.php';
    }

    protected function migrate($name){
        $tableName = Str::lower(Str::plural($name));
        $className = 'Create' . str_replace(' ', '', ucwords(str_replace('_', ' ', $tableName))) . 'Table';
        $migrationTemplete = str_replace(
            ['{{ class }}','{{ table }}'],
            [$className,$tableName],
            $this->getStub('migration')
        );

        file_put_contents($this->getMigrationPath($tableName),$migrationTemplete);
    }

    protected function model($name){

        $modelTemplete = str_replace(
            ['{{ class }}'],
            [($name)],
            $this->getStub('model')
        );

        file_put_contents(app_path("/Models/{$name}.php"),$modelTemplete);
    }

    protected function controller($name){

        $controllerTemplete = str_replace(
            [
            '{{ model }}',
            '{{ class }}',
            '{{modelVariablePlural}}',
            '{{ modelVariable }}',
            '{{viewPath}}'
            ],
            [
             ucfirst($name),
             ucfirst($name).'Controller',
             Str::lower(Str::plural($name)),
             Str::lower($name),
             'backend.'
             ],
            $this->getStub('controller')
        );
        if( !file_exists($path = app_path("/Http/Controllers/Admin")) ){
            mkdir($path,755);
        }
        file_put_contents(app_path("/Http/Controllers/Admin/{$name}Controller.php"),$controllerTemplete);
    }
    protected function views($name,$type){

        $modelVariablePlural =  Str::lower(Str::plural($name));

        /**view for create */
        $createTemplete = str_replace(
            [
            '{{ model }}',
            '{{ class }}',
            '{{modelVariablePlural}}',
            '{{ modelvariable }}',
            ],
            [
             ucfirst($name),
             ucfirst($name).'Controller',
             $modelVariablePlural,
             Str::lower($name),
             ],
            $this->getViewStub($type)
        );
        if( !file_exists($path = resource_path("views/backend/$modelVariablePlural/")) ){
            mkdir($path,755);
        }
        file_put_contents(resource_path("views/backend/$modelVariablePlural/$type.blade.php"),$createTemplete);
    }

    protected function generateRoute($name){
        $modelVariablePlural =  Str::lower(Str::plural($name));
        File::append( base_path('routes/web.php'),
        "Route::resource('$modelVariablePlural',App\Http\Controllers\Admin\\".ucfirst($name)."Controller::class);"
        );
    }
}
