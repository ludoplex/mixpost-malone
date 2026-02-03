<?php

namespace Inovector\Mixpost\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EntityResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'hex_color' => $this->hex_color,
            'image' => $this->image(),
            'accounts_count' => $this->whenCounted('accounts'),
            'sort_order' => $this->sort_order,
        ];
    }
}
