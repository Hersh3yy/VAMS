<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'media' => 'required|file|image|max:10240', // 10MB max
        ]);

        $file = $request->file('media');
        $path = $file->store('media', 'public');

        return response()->json([
            'path' => Storage::url($path),
            'type' => 'image',
        ]);
    }
} 