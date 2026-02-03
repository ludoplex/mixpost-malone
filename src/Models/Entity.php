<?php

namespace Inovector\Mixpost\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Inovector\Mixpost\Casts\AccountMediaCast;
use Inovector\Mixpost\Concerns\Model\HasUuid;

class Entity extends Model
{
    use HasFactory;
    use HasUuid;

    protected $table = 'mixpost_entities';

    protected $fillable = [
        'name',
        'hex_color',
        'media',
        'sort_order',
    ];

    protected $casts = [
        'media' => AccountMediaCast::class,
        'sort_order' => 'integer',
    ];

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class, 'entity_id');
    }

    public function image(): ?string
    {
        if ($this->media) {
            return Storage::disk($this->media['disk'])->url($this->media['path']);
        }

        return null;
    }

    public static function defaultColor(): string
    {
        return '#6366f1';
    }
}
