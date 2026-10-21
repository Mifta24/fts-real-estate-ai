<?php

namespace App\Models;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'property_id',
    'image_path',
    'image_url',
    'tags',
    'alt_text',
    'sort_order',
])]
class PropertyImage extends Model
{
    protected function casts(): array
    {
        return [
            'tags' => 'array',
        ];
    }

    public static function disk(): Filesystem
    {
        return Storage::disk(config('filesystems.property_images_disk'));
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function getImageSourceAttribute(): ?string
    {
        if ($this->image_url) {
            return $this->image_url;
        }

        return $this->image_path ? static::disk()->url($this->image_path) : null;
    }

    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags ?? [], true);
    }
}
