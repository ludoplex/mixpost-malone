<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Inovector\Mixpost\Commands\RunScheduledPosts;
use Inovector\Mixpost\Enums\PostScheduleStatus;
use Inovector\Mixpost\Enums\PostStatus;
use Inovector\Mixpost\Models\Account;
use Inovector\Mixpost\Models\Post;

beforeEach(function () {
    Bus::fake();
    Cache::clear();
});

it('stores last schedule run timestamp in cache', function () {
    Carbon::setTestNow(Carbon::create(2026, 2, 4, 12, 0, 0, 'UTC'));
    
    $this->artisan(RunScheduledPosts::class)->assertExitCode(0);
    
    $lastRun = Cache::get('mixpost-last-schedule-run');
    
    expect($lastRun)->not->toBeNull()
        ->and($lastRun->format('Y-m-d H:i'))->toBe('2026-02-04 12:00');
    
    Carbon::setTestNow();
});

it('processes scheduled posts that are due', function () {
    Carbon::setTestNow(Carbon::create(2026, 2, 4, 12, 0, 0, 'UTC'));
    
    $account = Account::factory()->create();
    
    // Post scheduled in the past (should be processed)
    $duePost = Post::factory()->state([
        'status' => PostStatus::SCHEDULED,
        'schedule_status' => PostScheduleStatus::PENDING,
        'scheduled_at' => Carbon::now()->subMinutes(5),
    ])->create();
    $duePost->accounts()->attach($account->id);
    
    $this->artisan(RunScheduledPosts::class)->assertExitCode(0);
    
    // Post should have schedule_status changed to PROCESSING
    $duePost->refresh();
    expect($duePost->schedule_status)->toBe(PostScheduleStatus::PROCESSING);
    
    Carbon::setTestNow();
});

it('ignores posts scheduled in the future', function () {
    Carbon::setTestNow(Carbon::create(2026, 2, 4, 12, 0, 0, 'UTC'));
    
    $account = Account::factory()->create();
    
    // Post scheduled in the future (should NOT be processed)
    $futurePost = Post::factory()->state([
        'status' => PostStatus::SCHEDULED,
        'schedule_status' => PostScheduleStatus::PENDING,
        'scheduled_at' => Carbon::now()->addHour(),
    ])->create();
    $futurePost->accounts()->attach($account->id);
    
    $this->artisan(RunScheduledPosts::class)->assertExitCode(0);
    
    $futurePost->refresh();
    expect($futurePost->schedule_status)->toBe(PostScheduleStatus::PENDING);
    
    Carbon::setTestNow();
});

it('ignores draft posts', function () {
    Carbon::setTestNow(Carbon::create(2026, 2, 4, 12, 0, 0, 'UTC'));
    
    $draftPost = Post::factory()->state([
        'status' => PostStatus::DRAFT,
        'schedule_status' => PostScheduleStatus::PENDING,
        'scheduled_at' => Carbon::now()->subMinutes(5),
    ])->create();
    
    $this->artisan(RunScheduledPosts::class)->assertExitCode(0);
    
    $draftPost->refresh();
    expect($draftPost->schedule_status)->toBe(PostScheduleStatus::PENDING);
    
    Carbon::setTestNow();
});

it('ignores already processing posts', function () {
    Carbon::setTestNow(Carbon::create(2026, 2, 4, 12, 0, 0, 'UTC'));
    
    $processingPost = Post::factory()->state([
        'status' => PostStatus::SCHEDULED,
        'schedule_status' => PostScheduleStatus::PROCESSING,
        'scheduled_at' => Carbon::now()->subMinutes(5),
    ])->create();
    
    $this->artisan(RunScheduledPosts::class)->assertExitCode(0);
    
    $processingPost->refresh();
    expect($processingPost->schedule_status)->toBe(PostScheduleStatus::PROCESSING);
    
    Carbon::setTestNow();
});
