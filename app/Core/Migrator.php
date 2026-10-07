<?php

namespace App\Core;

use Illuminate\Database\Capsule\Manager as Capsule;

class Migrator
{
    protected string $path;
    protected string $table = 'migrations';

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function run(): array
    {
        $schema = Capsule::schema();

        if (!$schema->hasTable($this->table)) {
            $schema->create($this->table, function ($table) {
                $table->increments('id');
                $table->string('migration');
                $table->integer('batch');
            });
        }

        $ran = Capsule::table($this->table)->pluck('migration')->all();
        $batch = ((int) Capsule::table($this->table)->max('batch')) + 1;
        $executed = [];

        foreach ($this->migrationFiles() as $file) {
            $name = basename($file);
            if (in_array($name, $ran, true)) {
                continue;
            }

            $migration = require $file;
            $migration->up();

            Capsule::table($this->table)->insert([
                'migration' => $name,
                'batch' => $batch,
            ]);

            $executed[] = $name;
        }

        return $executed;
    }

    public function fresh(): array
    {
        $schema = Capsule::schema();
        $schema->disableForeignKeyConstraints();

        foreach (array_reverse($this->migrationFiles()) as $file) {
            $migration = require $file;
            if (method_exists($migration, 'down')) {
                $migration->down();
            }
        }

        $schema->dropIfExists($this->table);
        $schema->enableForeignKeyConstraints();

        return $this->run();
    }

    protected function migrationFiles(): array
    {
        $files = glob(rtrim($this->path, '/\\') . DIRECTORY_SEPARATOR . '*.php') ?: [];
        sort($files);

        return $files;
    }
}
