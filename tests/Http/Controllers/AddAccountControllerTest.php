<?php

use Illuminate\Support\Facades\Config;
use Inovector\Mixpost\Models\Service;
use Inovector\Mixpost\Models\User;

beforeEach(function () {
    test()->user = User::factory()->create();
});

it('redirects to twitter auth url when adding twitter account', function () {
    $this->actingAs(test()->user);
    
    // Setup twitter service configuration
    Service::updateOrCreate(
        ['name' => 'twitter'],
        [
            'credentials' => [
                'client_id' => 'test_client_id',
                'client_secret' => 'test_client_secret',
            ],
        ]
    );
    
    $response = $this->get(route('mixpost.accounts.add', ['provider' => 'twitter']));
    
    // Should redirect to Twitter OAuth
    $response->assertStatus(302);
})->skip(fn () => true, 'Requires Twitter service setup');

it('requires authentication', function () {
    $this->get(route('mixpost.accounts.add', ['provider' => 'twitter']))
        ->assertRedirect(route('login'));
});
