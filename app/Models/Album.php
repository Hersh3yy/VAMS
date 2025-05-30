<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Album extends Model
{
    use HasFactory, HasUuids, LogsActivity;

    protected $fillable = [
        'title',
        'description',
        'order',
        'cover_image_path',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function images()
    {
        return $this->hasMany(AlbumImage::class)->orderBy('order');
    }

    public function media()
    {
        return $this->belongsToMany(Media::class)
            ->withPivot('order')
            ->orderBy('pivot_order');
    }

    public function getImagesCountAttribute()
    {
        return $this->media()->count();
    }
} 