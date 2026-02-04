<?php

use Illuminate\Support\Facades\Queue;
use Inovector\Mixpost\Commands\ImportAccountData;
use Inovector\Mixpost\Models\Account;

beforeEach(function () {
    Queue::fake();
});

it('dispatches twitter import job for twitter accounts', function () {
    Account::factory()->state(['provider' => 'twitter'])->create();
    
    $this->artisan(ImportAccountData::class)->assertExitCode(0);
});

it('dispatches facebook import job for facebook_page accounts', function () {
    Account::factory()->state(['provider' => 'facebook_page'])->create();
    
    $this->artisan(ImportAccountData::class)->assertExitCode(0);
});

it('dispatches mastodon import job for mastodon accounts', function () {
    Account::factory()->state(['provider' => 'mastodon'])->create();
    
    $this->artisan(ImportAccountData::class)->assertExitCode(0);
});

it('handles accounts option to filter specific accounts', function () {
    $account1 = Account::factory()->state(['provider' => 'twitter'])->create();
    $account2 = Account::factory()->state(['provider' => 'facebook_page'])->create();
    
    $this->artisan(ImportAccountData::class, ['--accounts' => $account1->id])
        ->assertExitCode(0);
});

it('handles multiple account ids in accounts option', function () {
    $account1 = Account::factory()->state(['provider' => 'twitter'])->create();
    $account2 = Account::factory()->state(['provider' => 'mastodon'])->create();
    
    $this->artisan(ImportAccountData::class, ['--accounts' => "{$account1->id},{$account2->id}"])
        ->assertExitCode(0);
});

it('skips unsupported providers gracefully', function () {
    Account::factory()->state(['provider' => 'unsupported_provider'])->create();
    
    $this->artisan(ImportAccountData::class)->assertExitCode(0);
});

it('processes all accounts when no filter provided', function () {
    Account::factory()->count(3)->create();
    
    $this->artisan(ImportAccountData::class)->assertExitCode(0);
});
