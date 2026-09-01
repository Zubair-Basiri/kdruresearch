<?php

if (!function_exists('currentUniversityId')) {
    function currentUniversityId()
    {
        $user = auth()->user();
        if (!$user) return null;

        // ----- GUEST HANDLING: use session -----
        if ($user->email === 'guest@example.com' && $user->role === 'user') {
            return session('guest_university_id', null);
        }
        // ----- END GUEST -----

        // Only Ministry Authority sees all
        if ($user->role === 'ministry_authority') {
            return null;
        }

        // For super_admin and admin, use their university_id
        if (in_array($user->role, ['super_admin', 'admin'])) {
            return $user->university_id;
        }

        // For regular users, get from lecturer
        if ($user->role === 'user') {
            return $user->lecturer?->university_id;
        }

        return null;
    }
}