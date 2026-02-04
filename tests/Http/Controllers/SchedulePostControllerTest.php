<?php

use Illuminate\Support\Carbon;
use Inovector\Mixpost\Enums\PostScheduleStatus;
use Inovector\Mixpost\Enums\PostStatus;
use Inovector\Mixpost\Models\Account;
use Inovector\Mixpost\Models\Post;
use Inovector\Mixpost\Models\User;

beforeEach(function () {
    test()->user = User::factory()->create();
});

it('can schedule a post for later', function () {
    $this->actingAs(test()->user);
    
    Carbon::setTestNow(Carbon::create(2026, 2, 4, 12, 0, 0, 'UTC'));
    
    $account = Account::factory()->create();
    
    $post = Post::factory()->state([
        'status' => PostStatus::DRAFT,
        'scheduled_at' => Carbon::now()->addHour(),
    ])->create();
    $post->accounts()->attach($account->id);
    $post->versions()->create([
        'account_id' => 0,
        'is_original' => true,
        'content' => [['body' => 'Test post content', 'media' => []]],
    ]);
    
    $response = $this->postJson(route('mixpost.posts.schedule', ['post' => $post->uuid]), [
        'postNow' => false,
    ]);
    
    $response->assertStatus(200);
    
    $post->refresh();
    expect($post->status)->toBe(PostStatus::SCHEDULED)
        ->and($post->schedule_status)->toBe(PostScheduleStatus::PENDING);
    
    Carbon::setTestNow();
});

it('can schedule a post to publish now', function () {
    $this->actingAs(test()->user);
    
    Carbon::setTestNow(Carbon::create(2026, 2, 4, 12, 0, 0, 'UTC'));
    
    $account = Account::factory()->create();
    
    $post = Post::factory()->state([
        'status' => PostStatus::DRAFT,
        'scheduled_at' => Carbon::now()->addHour(),
    ])->create();
    $post->accounts()->attach($account->id);
    $post->versions()->create([
        'account_id' => 0,
        'is_original' => true,
        'content' => [['body' => 'Test post content', 'media' => []]],
    ]);
    
    $response = $this->postJson(route('mixpost.posts.schedule', ['post' => $post->uuid]), [
        'postNow' => true,
    ]);
    
    $response->assertStatus(200);
    
    $post->refresh();
    expect($post->status)->toBe(PostStatus::SCHEDULED);
    
    Carbon::setTestNow();
});

it('prevents scheduling posts in the past', function () {
    $this->actingAs(test()->user);
    
    Carbon::setTestNow(Carbon::create(2026, 2, 4, 12, 0, 0, 'UTC'));
    
    $post = Post::factory()->state([
        'status' => PostStatus::DRAFT,
        'scheduled_at' => Carbon::now()->subHour(),
    ])->create();
    
    $response = $this->postJson(route('mixpost.posts.schedule', ['post' => $post->uuid]), [
        'postNow' => false,
    ]);
    
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['cannot_scheduled']);
    
    Carbon::setTestNow();
});

it('prevents scheduling already published posts', function () {
    $this->actingAs(test()->user);
    
    $post = Post::factory()->state([
        'status' => PostStatus::PUBLISHED,
        'scheduled_at' => Carbon::now()->addHour(),
    ])->create();
    
    $response = $this->postJson(route('mixpost.posts.schedule', ['post' => $post->uuid]), [
        'postNow' => false,
    ]);
    
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['in_history']);
});

it('prevents scheduling posts that are currently processing', function () {
    $this->actingAs(test()->user);
    
    $post = Post::factory()->state([
        'status' => PostStatus::SCHEDULED,
        'schedule_status' => PostScheduleStatus::PROCESSING,
        'scheduled_at' => Carbon::now()->addHour(),
    ])->create();
    
    $response = $this->postJson(route('mixpost.posts.schedule', ['post' => $post->uuid]), [
        'postNow' => false,
    ]);
    
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['publishing']);
});

it('requires postNow field', function () {
    $this->actingAs(test()->user);
    
    $post = Post::factory()->state([
        'status' => PostStatus::DRAFT,
        'scheduled_at' => Carbon::now()->addHour(),
    ])->create();
    
    $response = $this->postJson(route('mixpost.posts.schedule', ['post' => $post->uuid]), []);
    
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['postNow']);
});

it('prevents unauthorized users from scheduling posts', function () {
    $post = Post::factory()->create();
    
    $this->postJson(route('mixpost.posts.schedule', ['post' => $post->uuid]), [
        'postNow' => false,
    ])->assertUnauthorized();
});
