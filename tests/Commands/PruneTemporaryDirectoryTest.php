<?php

use Inovector\Mixpost\Commands\PruneTemporaryDirectory;

it('completes successfully when temporary directory does not exist', function () {
    $this->artisan(PruneTemporaryDirectory::class)
        ->assertExitCode(0);
});

it('accepts hours option', function () {
    $this->artisan(PruneTemporaryDirectory::class, ['--hours' => 5])
        ->assertExitCode(0);
});

it('accepts single hour option', function () {
    $this->artisan(PruneTemporaryDirectory::class, ['--hours' => 1])
        ->assertExitCode(0);
});

it('uses default 2 hours when no option provided', function () {
    $this->artisan(PruneTemporaryDirectory::class)
        ->expectsOutput('Pruning files older than 2 hours from the temporary directory...')
        ->assertExitCode(0);
})->skip(fn () => !is_dir(storage_path('app/mixpost-temp')), 'Temp directory does not exist');
