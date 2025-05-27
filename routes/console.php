<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command("application:fresh", function () {
    $this->call("migrate:fresh", ["--seed" => true]);
    $this->call("shield:super-admin");
    $this->call("shield:generate", ["--all" => true]);
})->purpose("Drop all tables and re-run all migrations with seeding");
