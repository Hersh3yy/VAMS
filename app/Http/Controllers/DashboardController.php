<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Mosaic;
use App\Models\Media;
use App\Models\Activity;
use Inertia\Inertia;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'totalAlbums' => Album::count(),
            'totalMosaics' => Mosaic::count(),
            'totalMedia' => Media::count(),
        ];

        $recentActivity = Activity::with('user')
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'type' => $activity->type,
                    'description' => $activity->description,
                    'user' => [
                        'name' => $activity->user->name,
                    ],
                    'created_at' => $activity->created_at->diffForHumans(),
                ];
            });

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentActivity' => $recentActivity,
        ]);
    }
} 