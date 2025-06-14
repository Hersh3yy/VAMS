<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Mosaic;
use App\Models\Media;
use App\Models\Activity;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $stats = [
            'totalAlbums' => Album::where('user_id', $user->id)->count(),
            'totalMosaics' => Mosaic::where('user_id', $user->id)->count(),
            'totalImages' => DB::table('album_images')
                ->join('albums', 'album_images.album_id', '=', 'albums.id')
                ->where('albums.user_id', $user->id)
                ->count(),
            'totalVideos' => DB::table('album_images')
                ->join('albums', 'album_images.album_id', '=', 'albums.id')
                ->where('albums.user_id', $user->id)
                ->where('album_images.properties->type', 'video')
                ->count(),
        ];

        // Get recent albums
        $recentAlbums = Album::where('user_id', $user->id)
            ->with('images')
            ->latest()
            ->take(6)
            ->get()
            ->map(function (Album $album) {
                return [
                    'id' => $album->id,
                    'title' => $album->title,
                    'description' => $album->description,
                    'cover_image_path' => $album->cover_image_path,
                    'images_count' => $album->images->count(),
                    'created_at' => $album->created_at->diffForHumans(),
                ];
            });

        // Get recent mosaics
        $recentMosaics = Mosaic::where('user_id', $user->id)
            ->latest()
            ->take(3)
            ->get()
            ->map(function ($mosaic) {
                return [
                    'id' => $mosaic->id,
                    'title' => $mosaic->title,
                    'description' => $mosaic->description,
                    'items_count' => count($mosaic->items ?? []),
                    'created_at' => $mosaic->created_at->diffForHumans(),
                ];
            });

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentAlbums' => $recentAlbums,
            'recentMosaics' => $recentMosaics,
        ]);
    }
}
