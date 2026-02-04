<?php

use Illuminate\Support\Facades\Bus;
use Inovector\Mixpost\Actions\PublishPost;
use Inovector\Mixpost\Enums\PostScheduleStatus;
use Inovector\Mixpost\Enums\PostStatus;
use Inovector\Mixpost\Jobs\AccountPublishPostJob;
use Inovector\Mixpost\Models\Account;
use Inovector\Mixpost\Models\Post;

beforeEach(function () {
    Bus::fake();
});

it('dispatches jobs for each account attached to the post', function () {
    $post = Post::factory()->state([
        'status' => PostStatus::SCHEDULED,
        'schedule_status' => PostScheduleStatus::PENDING,
    ])->create();
    
    $account1 = Account::factory()->create();
    $account2 = Account::factory()->create();
    
    $post->accounts()->attach([$account1->id, $account2->id]);
    
    (new PublishPost())($post);
    
    Bus::assertBatched(function ($batch) {
        return $batch->jobs->count() === 2;
    });
});

it('sets post to processing status', function () {
    $post = Post::factory()->state([
        'status' => PostStatus::SCHEDULED,
        'schedule_status' => PostScheduleStatus::PENDING,
    ])->create();
    
    $account = Account::factory()->create();
    $post->accounts()->attach($account->id);
    
    (new PublishPost())($post);
    
    $post->refresh();
    expect($post->schedule_status)->toBe(PostScheduleStatus::PROCESSING);
});

it('does not process already processing posts', function () {
    $post = Post::factory()->state([
        'status' => PostStatus::SCHEDULED,
        'schedule_status' => PostScheduleStatus::PROCESSING,
    ])->create();
    
    $account = Account::factory()->create();
    $post->accounts()->attach($account->id);
    
    (new PublishPost())($post);
    
    Bus::assertNothingBatched();
});

it('dispatches empty batch for posts without accounts', function () {
    $post = Post::factory()->state([
        'status' => PostStatus::SCHEDULED,
        'schedule_status' => PostScheduleStatus::PENDING,
    ])->create();
    
    // No accounts attached
    
    (new PublishPost())($post);
    
    Bus::assertBatched(function ($batch) {
        return $batch->jobs->count() === 0;
    });
});

it('uses the publish-post queue', function () {
    $post = Post::factory()->state([
        'status' => PostStatus::SCHEDULED,
        'schedule_status' => PostScheduleStatus::PENDING,
    ])->create();
    
    $account = Account::factory()->create();
    $post->accounts()->attach($account->id);
    
    (new PublishPost())($post);
    
    Bus::assertBatched(function ($batch) {
        return $batch->queue() === 'publish-post';
    });
});
