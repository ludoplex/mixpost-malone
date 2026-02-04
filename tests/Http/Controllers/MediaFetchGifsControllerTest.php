<?php

use Illuminate\Support\Facades\Http;
use Inovector\Mixpost\Models\Service;
use Inovector\Mixpost\Models\User;

beforeEach(function () {
    test()->user = User::factory()->create();
    Http::fake([
        'tenor.googleapis.com/*' => Http::response([
            'results' => [
                [
                    'id' => 'gif-123',
                    'content_description' => 'Test GIF',
                    'media_formats' => [
                        'tinygif' => [
                            'url' => 'https://example.com/gif.gif',
                        ],
                    ],
                ],
            ],
        ]),
    ]);
});

it('returns forbidden when tenor is not configured', function () {
    $this->actingAs(test()->user);
    
    // Ensure no tenor configuration
    Service::where('name', 'tenor')->delete();
    
    $this->getJson(route('mixpost.media.fetchGifs'))
        ->assertForbidden();
});

it('fetches gifs from tenor when configured', function () {
    $this->actingAs(test()->user);
    
    // Setup Tenor service configuration
    Service::updateOrCreate(
        ['name' => 'tenor'],
        [
            'credentials' => [
                'client_id' => 'test_tenor_api_key',
            ],
        ]
    );
    
    $response = $this->getJson(route('mixpost.media.fetchGifs'));
    
    $response->assertOk()
        ->assertJsonStructure([
            'data',
            'links' => ['next'],
        ]);
});

it('accepts keyword parameter for search', function () {
    $this->actingAs(test()->user);
    
    Service::updateOrCreate(
        ['name' => 'tenor'],
        [
            'credentials' => [
                'client_id' => 'test_tenor_api_key',
            ],
        ]
    );
    
    $response = $this->getJson(route('mixpost.media.fetchGifs', ['keyword' => 'happy']));
    
    $response->assertOk();
    
    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'q=happy');
    });
});

it('supports pagination', function () {
    $this->actingAs(test()->user);
    
    Service::updateOrCreate(
        ['name' => 'tenor'],
        [
            'credentials' => [
                'client_id' => 'test_tenor_api_key',
            ],
        ]
    );
    
    $response = $this->getJson(route('mixpost.media.fetchGifs', ['page' => 2]));
    
    $response->assertOk()
        ->assertJsonPath('links.next', '?page=3');
});

it('requires authentication', function () {
    $this->getJson(route('mixpost.media.fetchGifs'))
        ->assertUnauthorized();
});
