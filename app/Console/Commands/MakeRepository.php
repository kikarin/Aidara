<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeRepository extends Command
{
    protected $signature = 'make:repository {name}';

    protected $description = 'Create a new repository class';

    public function handle()
    {
        $name           = $this->argument('name');
        $repositoryName = Str::studly($name).'Repository';
        $namespace      = 'App\\Repositories';

        $stub = file_get_contents(base_path('stubs/repository.stub'));
        $stub = str_replace(['{{ namespace }}', '{{ rootNamespace }}', '{{ class }}'], [$namespace, 'App', $name], $stub);

        $path = app_path('Repositories/'.$repositoryName.'.php');
        if (! file_exists($path)) {
            file_put_contents($path, $stub);
            $this->info("Repository {$repositoryName} created successfully.");
        } else {
            $this->error("Repository {$repositoryName} already exists.");
        }
    }
}
