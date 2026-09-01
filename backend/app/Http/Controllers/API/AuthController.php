<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Lecturer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log; // ✅ Add this

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            if (!$user->is_approved) {
                Auth::logout();
                return response()->json(['message' => 'Your account is pending approval.'], 403);
            }

            $request->session()->regenerate();
            return response()->json([
                'user' => [
                    'id'            => $user->id,
                    'name'          => $user->name,
                    'email'         => $user->email,
                    'role'          => $user->role,
                    'university_id' => $user->university_id,
                ]
            ]);
        }

        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|unique:users',
            'password'    => 'required|string|min:8|confirmed',
            'role'        => 'required|in:user,admin,super_admin,lecturer_profile_admin,ministry_authority',
            'lecturer_id' => 'nullable|exists:lecturers,id',
            'university_id' => 'nullable|exists:universities,id',
        ]);

        $user = User::create([
            'name'        => $validated['name'],
            'email'       => $validated['email'],
            'password'    => Hash::make($validated['password']),
            'role'        => $validated['role'],
            'is_approved' => false,
            'university_id' => $validated['university_id'] ?? null,
        ]);

        if ($user->role === 'user' && !empty($validated['lecturer_id'])) {
            $lecturer = Lecturer::find($validated['lecturer_id']);
            if ($lecturer) {
                $lecturer->user_id = $user->id;
                $lecturer->save();
            }
        }

        return response()->json([
            'message' => 'User created successfully. Awaiting admin approval.',
            'user' => $user->only('id', 'name', 'email', 'role', 'university_id'),
        ], 201);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return response()->json(['message' => 'Logged out']);
    }

    public function user(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $universityId = $user->university_id;

            // For guest, use session university (if set)
            if ($user->email === 'guest@example.com' && $user->role === 'user') {
                $universityId = session('guest_university_id', null);
            }

            return response()->json([
                'id'          => $user->id,
                'name'        => $user->name,
                'email'       => $user->email,
                'role'        => $user->role,
                'lecturer_id' => $user->lecturer?->id,
                'university_id' => $universityId,
            ]);
        }
        return response()->json(['message' => 'Not authenticated'], 401);
    }

    public function guestLogin(Request $request)
    {
        // Find or create the shared guest user
        $guest = User::firstOrCreate(
            ['email' => 'guest@example.com'],
            [
                'name' => 'Guest User',
                'password' => Hash::make('password'),
                'role' => 'user',
                'is_approved' => true,
                'university_id' => null, // never used for guests
            ]
        );

        Auth::login($guest);
        $request->session()->regenerate();

        // Initialize session university for this guest (if not already set)
        if (!session()->has('guest_university_id')) {
            session()->put('guest_university_id', null);
        }

        return response()->json([
            'user' => [
                'id'            => $guest->id,
                'name'          => $guest->name,
                'email'         => $guest->email,
                'role'          => $guest->role,
                'university_id' => session('guest_university_id'),
            ]
        ]);
    }

    /**
     * Update the authenticated user's university_id.
     * For guests, store in session; for normal users, update DB.
     */
    public function updateUniversity(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            $validated = $request->validate([
                'university_id' => 'required|exists:universities,id',
            ]);

            // GUEST: store in session
            if ($user->email === 'guest@example.com' && $user->role === 'user') {
                session()->put('guest_university_id', $validated['university_id']);
                session()->save(); // Ensure session is persisted

                // Reload session data to confirm
                $universityId = session('guest_university_id');

                return response()->json([
                    'message' => 'University updated successfully',
                    'user' => [
                        'id'            => $user->id,
                        'name'          => $user->name,
                        'email'         => $user->email,
                        'role'          => $user->role,
                        'university_id' => $universityId,
                    ],
                ]);
            }

            // For non‑guests, update DB
            User::where('id', $user->id)->update([
                'university_id' => $validated['university_id'],
            ]);

            $user = User::find($user->id);

            return response()->json([
                'message' => 'University updated successfully',
                'user' => [
                    'id'            => $user->id,
                    'name'          => $user->name,
                    'email'         => $user->email,
                    'role'          => $user->role,
                    'university_id' => $user->university_id,
                ],
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('updateUniversity error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update university: ' . $e->getMessage(),
            ], 500);
        }
    }
}