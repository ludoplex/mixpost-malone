<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Inovector\Mixpost\Models\User;

beforeEach(function () {
    test()->user = User::factory()->create();
});

it('shows system status page', function () {
    $this->actingAs(test()->user);
    
    $this->publishAssets();
    
    $this->get(route('mixpost.system.status'))
        ->assertOk()
        ->assertInertia(fn(Assert $page) => $page
            ->component('System/Status')
            ->has('health')
            ->has('tech')
        );
});

it('displays health information', function () {
    $this->actingAs(test()->user);
    
    $this->publishAssets();
    
    $this->get(route('mixpost.system.status'))
        ->assertInertia(fn(Assert $page) => $page
            ->has('health.env')
            ->has('health.debug')
            ->has('health.horizon_status')
            ->has('health.has_queue_connection')
            ->has('health.last_scheduled_run')
        );
});

it('displays technical information', function () {
    $this->actingAs(test()->user);
    
    $this->publishAssets();
    
    $this->get(route('mixpost.system.status'))
        ->assertInertia(fn(Assert $page) => $page
            ->has('tech.cache_driver')
            ->has('tech.base_path')
            ->has('tech.disk')
            ->has('tech.ffmpeg_status')
            ->has('tech.versions')
        );
});

it('shows error when scheduler never ran', function () {
    Cache::forget('mixpost-last-schedule-run');
    
    $this->actingAs(test()->user);
    
    $this->publishAssets();
    
    $this->get(route('mixpost.system.status'))
        ->assertInertia(fn(Assert $page) => $page
            ->where('health.last_scheduled_run.variant', 'error')
            ->where('health.last_scheduled_run.message', 'It never started')
        );
});

it('shows success when scheduler ran recently', function () {
    Cache::put('mixpost-last-schedule-run', Carbon::now('UTC')->subMinutes(2));
    
    $this->actingAs(test()->user);
    
    $this->publishAssets();
    
    $this->get(route('mixpost.system.status'))
        ->assertInertia(fn(Assert $page) => $page
            ->where('health.last_scheduled_run.variant', 'success')
        );
});

it('shows warning when scheduler ran long ago', function () {
    Cache::put('mixpost-last-schedule-run', Carbon::now('UTC')->subMinutes(15));
    
    $this->actingAs(test()->user);
    
    $this->publishAssets();
    
    $this->get(route('mixpost.system.status'))
        ->assertInertia(fn(Assert $page) => $page
            ->where('health.last_scheduled_run.variant', 'warning')
        );
});

it('redirects unauthenticated users to login', function () {
    $this->get(route('mixpost.system.status'))
        ->assertRedirect(route('login'));
});
