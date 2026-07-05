<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
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
        $users = User::select('id', 'name', 'email', 'role', 'is_approved')->get();
        return response()->json($users);
    }

    /**
     * Display the specified user.
     */
    public function show($id)
    {
        $user = User::select('id', 'name', 'email', 'role')->findOrFail($id);
        return response()->json($user);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $rules = [
            'name'  => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'role'  => 'required|in:user,admin,super_admin',
        ];

        // Only validate password if it's present
        if ($request->filled('password')) {
            $rules['password'] = 'required|string|min:8|confirmed';
        }

        $validated = $request->validate($rules);

        $data = [
            'name'  => $validated['name'],
            'email' => $validated['email'],
            'role'  => $validated['role'],
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        return response()->json([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role,
        ]);
    }

    /**
     * Remove the specified user.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $rules = [
            'name' => 'required|string|max:255',
        ];

        // If password fields are present, validate them
        if ($request->filled('current_password') || $request->filled('new_password')) {
            $rules['current_password'] = 'required|string';
            $rules['new_password'] = 'required|string|min:8|confirmed';
        }

        $validated = $request->validate($rules);

        // Check current password if provided
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

    // public function approve($id)
    // {
    //     $user = User::findOrFail($id);
    //     if ($user->is_approved) {
    //         return response()->json(['message' => 'User already approved'], 200);
    //     }
    //     $user->is_approved = true;
    //     $user->save();
    //     return response()->json(['message' => 'User approved successfully']);
    // }

    public function toggleApproval($id)
    {
        $user = User::findOrFail($id);
        $user->is_approved = !$user->is_approved;
        $user->save();

        if ($user->is_approved) {
            try {
                Mail::to($user->email)->send(new UserApprovedMail($user));
            } catch (\Exception $e) {
                Log::error('Approval email failed: ' . $e->getMessage());
                // Return error details for debugging (remove in production)
                return response()->json([
                    'message' => 'User approval status updated but email failed: ' . $e->getMessage(),
                    'is_approved' => $user->is_approved,
                ], 500);
            }
        }

        return response()->json([
            'message' => 'User approval status updated',
            'is_approved' => $user->is_approved,
        ]);
    }

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
        // No json_decode needed – the cast already returns an array
        $saved = $user->theme_settings ?? [];
        $settings = array_merge($defaults, $saved);
        return response()->json(['settings' => $settings]);
    }
}