<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Lecturer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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

            // Check if account is approved
            if (!$user->is_approved) {
                Auth::logout();
                return response()->json(['message' => 'Your account is pending approval.'], 403);
            }

            $request->session()->regenerate();
            return response()->json([
                'user' => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role,
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
            'role'        => 'required|in:user,admin,super_admin',
            'lecturer_id' => 'nullable|exists:lecturers,id',  // new
        ]);

        $user = User::create([
            'name'        => $validated['name'],
            'email'       => $validated['email'],
            'password'    => Hash::make($validated['password']),
            'role'        => $validated['role'],
            'is_approved' => false,
        ]);

        // If the user is a lecturer (role 'user') and a lecturer_id is provided, link them
        if ($user->role === 'user' && !empty($validated['lecturer_id'])) {
            $lecturer = Lecturer::find($validated['lecturer_id']);
            if ($lecturer) {
                $lecturer->user_id = $user->id;
                $lecturer->save();
            }
        }

        return response()->json([
            'message' => 'User created successfully. Awaiting admin approval.',
            'user' => $user->only('id', 'name', 'email', 'role'),
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
            // Load the user with the lecturer relationship
            $user = User::with('lecturer')->find(Auth::id());
            return response()->json([
                'id'          => $user->id,
                'name'        => $user->name,
                'email'       => $user->email,
                'role'        => $user->role,
                'lecturer_id' => $user->lecturer?->id,
            ]);
        }
        return response()->json(['message' => 'Not authenticated'], 401);
    }

    public function guestLogin(Request $request)
    {
        // Find or create a guest user
        $guest = User::firstOrCreate(
            ['email' => 'guest@example.com'],
            [
                'name' => 'Guest User',
                'password' => Hash::make('password'),
                'role' => 'user',
                'is_approved' => true,
            ]
        );

        Auth::login($guest);
        $request->session()->regenerate();

        return response()->json([
            'user' => [
                'id'    => $guest->id,
                'name'  => $guest->name,
                'email' => $guest->email,
                'role'  => $guest->role,
            ]
        ]);
    }
}