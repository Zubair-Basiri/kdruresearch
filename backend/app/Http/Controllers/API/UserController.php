<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Lecturer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserApprovedMail;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index()
    {
        $user = Auth::user();
        $query = User::select('id', 'name', 'email', 'role', 'is_approved', 'university_id', 'created_at');

        if ($user->role === 'ministry_authority') {
            // Show all users
        } elseif (in_array($user->role, ['super_admin', 'admin'])) {
            $universityId = $user->university_id;
            if ($universityId) {
                $query->where('university_id', $universityId);
            } else {
                return response()->json([]);
            }
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query->orderBy('created_at', 'desc');

        return response()->json($query->get());
    }

    /**
     * Display the specified user.
     */
    public function show($id)
    {
        $user = User::select('id', 'name', 'email', 'role', 'university_id')->findOrFail($id);
        return response()->json($user);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['ministry_authority', 'super_admin', 'admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|unique:users',
            'password'     => 'required|string|min:8|confirmed',
            'role'         => ['required', Rule::in(['user', 'admin', 'super_admin', 'lecturer_profile_admin', 'ministry_authority'])],
            'lecturer_id'  => 'nullable|exists:lecturers,id',
            'university_id'=> 'nullable|exists:universities,id',
            'is_approved'  => 'sometimes|boolean',
        ]);

        if ($validated['role'] === 'ministry_authority') {
            $validated['university_id'] = null;
        }

        // Only ministry_authority can assign the ministry_authority role
        if ($validated['role'] === 'ministry_authority' && $user->role !== 'ministry_authority') {
            return response()->json(['message' => 'Only a ministry authority can assign the ministry_authority role.'], 403);
        }

        // For super_admin and admin, restrict university_id to their own
        if (in_array($user->role, ['super_admin', 'admin'])) {
            $allowedUniversityId = $user->university_id;
            if (!$allowedUniversityId) {
                return response()->json(['message' => 'No university assigned.'], 403);
            }
            if (isset($validated['university_id']) && $validated['university_id'] != $allowedUniversityId) {
                return response()->json(['message' => 'You can only create users for your own university.'], 403);
            }
            $validated['university_id'] = $allowedUniversityId;
        }

        $newUser = User::create([
            'name'         => $validated['name'],
            'email'        => $validated['email'],
            'password'     => Hash::make($validated['password']),
            'role'         => $validated['role'],
            'is_approved'  => $validated['is_approved'] ?? false,
            'university_id'=> $validated['university_id'] ?? null,
        ]);

        // If role is 'user', link lecturer
        if ($newUser->role === 'user' && !empty($validated['lecturer_id'])) {
            $lecturer = Lecturer::find($validated['lecturer_id']);
            if ($lecturer) {
                $lecturer->user_id = $newUser->id;
                $lecturer->save();
            }
        }

        return response()->json($newUser, 201);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $targetUser = User::findOrFail($id);

        // Check permission
        if ($user->role === 'ministry_authority') {
            // Can update any user
        } elseif (in_array($user->role, ['super_admin', 'admin'])) {
            if ($targetUser->university_id != $user->university_id) {
                return response()->json(['message' => 'You can only update users from your own university.'], 403);
            }
            if ($request->has('university_id') && $request->university_id != $user->university_id) {
                return response()->json(['message' => 'You cannot change the university.'], 403);
            }
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $rules = [
            'name'  => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($targetUser->id)],
            'role'  => ['required', Rule::in(['user', 'admin', 'super_admin', 'lecturer_profile_admin', 'ministry_authority'])],
            'university_id' => 'nullable|exists:universities,id',
        ];

        // Only ministry_authority can assign the ministry_authority role
        if ($request->role === 'ministry_authority' && $user->role !== 'ministry_authority') {
            return response()->json(['message' => 'Only a ministry authority can assign the ministry_authority role.'], 403);
        }

        if ($request->filled('password')) {
            $rules['password'] = 'required|string|min:8|confirmed';
        }

        $validated = $request->validate($rules);

        if ($validated['role'] === 'ministry_authority' || $targetUser->role === 'ministry_authority') {
            $validated['university_id'] = null;
        }

        $data = [
            'name'  => $validated['name'],
            'email' => $validated['email'],
            'role'  => $validated['role'],
            'university_id' => $validated['university_id'] ?? null,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($validated['password']);
        }

        $targetUser->update($data);

        return response()->json([
            'id'    => $targetUser->id,
            'name'  => $targetUser->name,
            'email' => $targetUser->email,
            'role'  => $targetUser->role,
            'university_id' => $targetUser->university_id,
        ]);
    }

    /**
     * Remove the specified user.
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $targetUser = User::findOrFail($id);

        if ($user->role === 'ministry_authority') {
            // Can delete any user
        } elseif (in_array($user->role, ['super_admin', 'admin'])) {
            if ($targetUser->university_id != $user->university_id) {
                return response()->json(['message' => 'You can only delete users from your own university.'], 403);
            }
            if ($user->id == $targetUser->id) {
                return response()->json(['message' => 'You cannot delete yourself.'], 403);
            }
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $targetUser->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }

    /**
     * Update the current user's profile (name and password).
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $rules = [
            'name' => 'required|string|max:255',
        ];

        if ($request->filled('current_password') || $request->filled('new_password')) {
            $rules['current_password'] = 'required|string';
            $rules['new_password'] = 'required|string|min:8|confirmed';
        }

        $validated = $request->validate($rules);

        if ($request->filled('current_password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'message' => 'The current password is incorrect.',
                    'errors' => ['current_password' => ['Current password is incorrect.']]
                ], 422);
            }
            $user->password = Hash::make($request->new_password);
        }

        $user->name = $validated['name'];
        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
            ]
        ]);
    }

    /**
     * Toggle approval status of a user.
     */
    public function toggleApproval($id)
    {
        $user = Auth::user();
        $targetUser = User::findOrFail($id);

        // Check permission
        if ($user->role === 'ministry_authority') {
            // Can update any user
        } elseif (in_array($user->role, ['super_admin', 'admin'])) {
            if ($targetUser->university_id != $user->university_id) {
                return response()->json(['message' => 'You can only update users from your own university.'], 403);
            }
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $targetUser->is_approved = !$targetUser->is_approved;
        $targetUser->save();

        if ($targetUser->is_approved) {
            try {
                Mail::to($targetUser->email)->send(new UserApprovedMail($targetUser));
            } catch (\Exception $e) {
                Log::error('Approval email failed: ' . $e->getMessage());
                return response()->json([
                    'message' => 'User approval status updated but email failed: ' . $e->getMessage(),
                    'is_approved' => $targetUser->is_approved,
                ], 500);
            }
        }

        return response()->json([
            'message' => 'User approval status updated',
            'is_approved' => $targetUser->is_approved,
        ]);
    }

    /**
     * Save theme settings for the current user.
     */
    public function saveThemeSettings(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        $validated = $request->validate([
            'settings' => 'required|array',
        ]);
        $updated = User::where('id', $user->id)->update([
            'theme_settings' => json_encode($validated['settings'])
        ]);
        if ($updated) {
            return response()->json(['message' => 'Theme settings saved']);
        }
        return response()->json(['message' => 'Failed to save settings'], 500);
    }

    /**
     * Load theme settings for the current user.
     */
    public function loadThemeSettings(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        $defaults = [
            'sidebar_color' => 'sidebar-default',
            'sidebar_menu_style' => 'sidebar-default',
            'header_navbar' => 'navs-default',
            'theme_scheme' => 'light',
            'theme_scheme_direction' => 'ltr',
            'theme_font_size' => 'theme-fs-sm',
            'theme_color' => [
                'value' => 'theme-color-default',
                'colors' => [
                    '--bs-primary' => '#3a57e8',
                    '--bs-info' => '#08B1BA'
                ]
            ]
        ];
        $saved = $user->theme_settings ?? [];
        $settings = array_merge($defaults, $saved);
        return response()->json(['settings' => $settings]);
    }
}