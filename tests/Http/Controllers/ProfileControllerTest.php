<?php

use Inertia\Testing\AssertableInertia as Assert;
use Inovector\Mixpost\Models\User;

beforeEach(function () {
    test()->user = User::factory()->create();
});

it('shows profile page', function () {
    $this->actingAs(test()->user);
    
    $this->publishAssets();
    
    $this->get(route('mixpost.profile'))
        ->assertOk()
        ->assertInertia(fn(Assert $page) => $page
            ->component('Profile')
        );
});

it('redirects unauthenticated users to login', function () {
    $this->get(route('mixpost.profile'))
        ->assertRedirect(route('login'));
});
