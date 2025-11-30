<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Album;
use App\Models\Mosaic;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController
{
    public function index()
    {
        $user = Auth::user();

        // Calculate stats using efficient database queries
        $totalAlbums = Album::where('user_id', $user->id)->count();
        $totalMosaics = Mosaic::where('user_id', $user->id)->count();

        // Count total album images
        $totalAlbumImages = DB::table('album_images')
            ->join('albums', 'album_images.album_id', '=', 'albums.id')
            ->where('albums.user_id', $user->id)
            ->count();

        // Count videos using PostgreSQL JSON syntax
        $totalVideos = DB::table('album_images')
            ->join('albums', 'album_images.album_id', '=', 'albums.id')
            ->where('albums.user_id', $user->id)
            ->where(function ($query) {
                $query->whereRaw("album_images.properties->>'type' = 'video'")
                    ->orWhereRaw("album_images.properties->>'is_video' = 'true'")
                    ->orWhere('album_images.path', 'like', '%youtube.com%')
                    ->orWhere('album_images.path', 'like', '%youtu.be%')
                    ->orWhere('album_images.path', 'like', '%vimeo.com%');
            })
            ->count();

        $stats = [
            'totalAlbums' => $totalAlbums,
            'totalMosaics' => $totalMosaics,
            'totalImages' => $totalAlbumImages - $totalVideos,
            'totalVideos' => $totalVideos,
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

        // Get recent entities (albums and mosaics combined)
        $recentEntities = collect([
            ...$recentAlbums->map(function ($album) {
                return [
                    'id' => $album['id'],
                    'type' => 'album',
                    'title' => $album['title'],
                    'description' => $album['description'],
                    'cover_image_path' => $album['cover_image_path'],
                    'items_count' => $album['images_count'],
                    'created_at' => $album['created_at'],
                    'url' => route('albums.show', $album['id']),
                ];
            }),
            ...$recentMosaics->map(function ($mosaic) {
                return [
                    'id' => $mosaic['id'],
                    'type' => 'mosaic',
                    'title' => $mosaic['title'],
                    'description' => $mosaic['description'],
                    'cover_image_path' => null,
                    'items_count' => $mosaic['items_count'],
                    'created_at' => $mosaic['created_at'],
                    'url' => route('mosaics.show', $mosaic['id']),
                ];
            }),
        ])->sortByDesc('created_at')->take(6)->values();

        // Get recent activities using ActivityService
        $activityService = app(\App\Services\ActivityService::class);
        $recentActivities = $activityService->formatActivitiesForDisplay(
            $activityService->getRecentActivities($user, 5)
        );

        return Inertia::render('Dashboard', [
            'user' => ['name' => $user->name],
            'stats' => $stats,
            'recentAlbums' => $recentAlbums,
            'recentMosaics' => $recentMosaics,
            'recentEntities' => $recentEntities,
            'recentActivities' => $recentActivities,
        ]);
    }
}
