<?php

namespace App\Http\Controllers\API\Addition;

use App\Http\Controllers\Controller;
use App\Models\University;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UniversityController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $query = University::query();

        // Allow guests to see all universities (for selection)
        if ($user && $user->email === 'guest@example.com' && $user->role === 'user') {
            return response()->json($query->orderBy('name')->get());
        }

        // For non-guest users, apply restrictions
        if ($user && !in_array($user->role, ['ministry_authority', 'super_admin'])) {
            if ($user->university_id) {
                $query->where('id', $user->university_id);
            } else {
                return response()->json([]);
            }
        }

        return response()->json($query->orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'ministry_authority') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $name = trim($validated['name']);

        // Check if a university with this name exists (including soft-deleted)
        $existing = University::withTrashed()->where('name', $name)->first();

        if ($existing) {
            if ($existing->trashed()) {
                // Restore the soft-deleted record
                $existing->restore();
                return response()->json($existing, 200);
            } else {
                return response()->json([
                    'message' => 'A university with this name already exists.',
                    'errors' => ['name' => ['The name has already been taken.']]
                ], 422);
            }
        }

        $university = University::create(['name' => $name]);
        return response()->json($university, 201);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'ministry_authority') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $university = University::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $name = trim($validated['name']);

        // If the name is different, check for conflicts (including soft-deleted)
        if ($university->name !== $name) {
            $existing = University::withTrashed()
                ->where('name', $name)
                ->where('id', '!=', $university->id)
                ->first();

            if ($existing) {
                if ($existing->trashed()) {
                    // If the conflicting record is soft-deleted, we could either merge or reject.
                    // For simplicity, we'll reject with a message to restore or use a different name.
                    return response()->json([
                        'message' => 'A university with this name exists but is soft-deleted. Please restore it first.',
                        'errors' => ['name' => ['The name is already used (soft-deleted).']]
                    ], 422);
                } else {
                    return response()->json([
                        'message' => 'A university with this name already exists.',
                        'errors' => ['name' => ['The name has already been taken.']]
                    ], 422);
                }
            }
        }

        $university->update(['name' => $name]);
        return response()->json($university);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'ministry_authority') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $university = University::findOrFail($id);
        $university->delete();
        return response()->json(['message' => 'University deleted']);
    }
}