<?php

use Inertia\Testing\AssertableInertia as Assert;
use Inovector\Mixpost\Models\User;

beforeEach(function () {
    test()->user = User::factory()->create();
});

it('shows system logs page', function () {
    $this->actingAs(test()->user);
    
    $this->publishAssets();
    
    $this->get(route('mixpost.system.logs'))
        ->assertOk()
        ->assertInertia(fn(Assert $page) => $page
            ->component('System/Logs')
            ->has('logs')
        );
});

it('displays available log files', function () {
    $this->actingAs(test()->user);
    
    $this->publishAssets();
    
    $this->get(route('mixpost.system.logs'))
        ->assertInertia(fn(Assert $page) => $page
            ->has('logs')
        );
});

it('redirects unauthenticated users to login', function () {
    $this->get(route('mixpost.system.logs'))
        ->assertRedirect(route('login'));
});
