<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'path',
        'mime_type',
        'size',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function albums()
    {
        return $this->belongsToMany(Album::class);
    }

    public function mosaics()
    {
        return $this->belongsToMany(Mosaic::class);
    }
}
