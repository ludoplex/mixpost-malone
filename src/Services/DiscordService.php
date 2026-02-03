<?php

namespace Inovector\Mixpost\Services;

use Inovector\Mixpost\Abstracts\Service;
use Inovector\Mixpost\Enums\ServiceGroup;

class DiscordService extends Service
{
    public static function group(): ServiceGroup
    {
        return ServiceGroup::SOCIAL;
    }

    public static function form(): array
    {
        return [
            'client_id' => '',
            'client_secret' => '',
            'bot_token' => '',
        ];
    }

    public static function formRules(): array
    {
        return [
            'client_id' => ['required'],
            'client_secret' => ['required'],
            'bot_token' => ['required'],
        ];
    }

    public static function formMessages(): array
    {
        return [
            'client_id' => 'The Application ID is required.',
            'client_secret' => 'The Client Secret is required.',
            'bot_token' => 'The Bot Token is required.',
        ];
    }
}