<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlbumImage extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'path',
        'title',
        'caption',
        'author',
        'order',
    ];

    public function album()
    {
        return $this->belongsTo(Album::class);
    }
} 