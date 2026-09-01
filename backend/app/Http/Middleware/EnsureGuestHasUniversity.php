<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureGuestHasUniversity
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if ($user && $user->email === 'guest@example.com' && $user->role === 'user') {
            $universityId = session('guest_university_id', null);
            if (is_null($universityId)) {
                return response()->json([
                    'message' => 'Please select a university first.',
                ], 422);
            }
        }
        return $next($request);
    }
}