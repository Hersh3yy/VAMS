<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Album;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'pending_approvals' => User::where('is_approved', false)->count(),
            'total_albums' => Album::count(),
            'total_admins' => User::where('is_admin', true)->count(),
        ];
        
        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
        ]);
    }
}
