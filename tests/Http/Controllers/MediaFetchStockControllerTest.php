<?php

use Illuminate\Support\Facades\Http;
use Inovector\Mixpost\Models\Service;
use Inovector\Mixpost\Models\User;

beforeEach(function () {
    test()->user = User::factory()->create();
    
    Http::fake([
        'api.unsplash.com/*' => Http::response([
            'results' => [
                [
                    'id' => 'photo-123',
                    'user' => [
                        'name' => 'Test Photographer',
                        'links' => [
                            'html' => 'https://unsplash.com/@testuser',
                        ],
                    ],
                    'urls' => [
                        'regular' => 'https://images.unsplash.com/photo-123-regular',
                        'thumb' => 'https://images.unsplash.com/photo-123-thumb',
                    ],
                    'links' => [
                        'download_location' => 'https://api.unsplash.com/photos/photo-123/download',
                    ],
                ],
            ],
        ]),
    ]);
});

it('fetches stock photos from unsplash when configured', function () {
    $this->actingAs(test()->user);
    
    // Setup Unsplash service configuration
    Service::updateOrCreate(
        ['name' => 'unsplash'],
        [
            'credentials' => [
                'client_id' => 'test_unsplash_access_key',
            ],
        ]
    );
    
    $response = $this->getJson(route('mixpost.media.fetchStock'));
    
    $response->assertOk()
        ->assertJsonStructure([
            'data',
            'links' => ['next'],
        ]);
});

it('accepts keyword parameter for search', function () {
    $this->actingAs(test()->user);
    
    Service::updateOrCreate(
        ['name' => 'unsplash'],
        [
            'credentials' => [
                'client_id' => 'test_unsplash_access_key',
            ],
        ]
    );
    
    $response = $this->getJson(route('mixpost.media.fetchStock', ['keyword' => 'nature']));
    
    $response->assertOk();
});

it('supports pagination', function () {
    $this->actingAs(test()->user);
    
    Service::updateOrCreate(
        ['name' => 'unsplash'],
        [
            'credentials' => [
                'client_id' => 'test_unsplash_access_key',
            ],
        ]
    );
    
    $response = $this->getJson(route('mixpost.media.fetchStock', ['page' => 2]));
    
    $response->assertOk()
        ->assertJsonPath('links.next', '?page=3');
});

it('requires authentication', function () {
    $this->getJson(route('mixpost.media.fetchStock'))
        ->assertUnauthorized();
});
