<?php

namespace App\Http\Controllers;

use App\Services\MediaService;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    protected $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    public function upload(Request $request)
    {
        $request->validate([
            'media' => 'required|file|image|max:30720', // 30MB max
        ]);

        $result = $this->mediaService->storeFile($request->file('media'));

        return response()->json($result);
    }
} 