<?php

declare(strict_types=1);

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(
    Tests\TestCase::class,
)->in('Feature', 'Unit');

uses(RefreshDatabase::class)->in('Feature', 'Unit');

// Seed roles before each test (Feature and Unit)
uses()->beforeEach(function () {
    $this->seed(RoleSeeder::class);
})->in('Feature', 'Unit');

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});
