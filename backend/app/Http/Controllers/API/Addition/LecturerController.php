<?php

namespace App\Http\Controllers\API\Addition;

use App\Http\Controllers\Controller;
use App\Models\Lecturer;
use App\Models\Faculty;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LecturerController extends Controller
{
    /**
     * Display a listing of lecturers with university, faculty, department.
     * Filtered by the current user's university (unless super_admin or ministry_authority).
     */
    public function index(Request $request)
    {
        $universityId = currentUniversityId();

        $query = Lecturer::with(['university', 'faculty', 'department']);

        if ($universityId) {
            $query->whereHas('faculty', function ($q) use ($universityId) {
                $q->where('university_id', $universityId);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('lecturername', 'like', "%{$search}%");
        }

        $lecturers = $query->latest()->get();

        return response()->json($lecturers);
    }

    /**
     * Get a list of lecturers for dropdown selects.
     */
    public function forDropdown(Request $request)
    {
        $universityId = currentUniversityId();

        $query = Lecturer::with(['faculty', 'department', 'university']);

        if ($universityId) {
            $query->whereHas('faculty', function ($q) use ($universityId) {
                $q->where('university_id', $universityId);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('lecturername', 'like', "%{$search}%");
        }

        $lecturers = $query->get();

        return response()->json($lecturers);
    }

    /**
     * Store a newly created lecturer.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'lecturername' => 'required|string|max:255',
            'university_id' => 'required|exists:universities,id', // now required for all
            'faculty_id'   => 'nullable|exists:faculties,id',
            'department_id'=> 'nullable|exists:departments,id',
            'grade'        => 'nullable|string|max:255',
            'qualification'=> 'nullable|string|max:255',
            'specialized_area' => 'nullable|array',
            'user_id'      => 'nullable|exists:users,id',
        ]);

        $universityId = $validated['university_id'];

        // For non-ministry/super_admin users, enforce their own university
        if (!in_array($user->role, ['ministry_authority', 'super_admin'])) {
            $userUniId = currentUniversityId();
            if ($universityId != $userUniId) {
                return response()->json(['message' => 'You can only create lecturers for your own university.'], 403);
            }
        }

        // Validate faculty belongs to this university
        if (!empty($validated['faculty_id'])) {
            $faculty = Faculty::where('id', $validated['faculty_id'])
                ->where('university_id', $universityId)
                ->first();
            if (!$faculty) {
                return response()->json(['message' => 'Invalid faculty for this university'], 422);
            }
        }

        // Validate department belongs to this university
        if (!empty($validated['department_id'])) {
            $department = Department::where('id', $validated['department_id'])
                ->where('university_id', $universityId)
                ->first();
            if (!$department) {
                return response()->json(['message' => 'Invalid department for this university'], 422);
            }
        }

        $lecturer = Lecturer::create($validated);

        return response()->json($lecturer, 201);
    }

    /**
     * Display the specified lecturer.
     */
    public function show($id)
    {
        $universityId = currentUniversityId();

        $lecturer = Lecturer::with(['university', 'faculty', 'department', 'academicPapers'])
            ->findOrFail($id);

        if ($universityId) {
            $faculty = $lecturer->faculty;
            if (!$faculty || $faculty->university_id != $universityId) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        return response()->json($lecturer);
    }

    /**
     * Update the specified lecturer.
     */
    public function update(Request $request, $id)
    {
        $lecturer = Lecturer::findOrFail($id);
        $user = Auth::user();

        // Authorization: ensure the user can edit this lecturer
        $universityId = currentUniversityId();
        if ($universityId) {
            $faculty = $lecturer->faculty;
            if (!$faculty || $faculty->university_id != $universityId) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        $validated = $request->validate([
            'lecturername' => 'sometimes|string|max:255',
            'university_id' => 'sometimes|exists:universities,id',
            'faculty_id'   => 'nullable|exists:faculties,id',
            'department_id'=> 'nullable|exists:departments,id',
            'grade'        => 'nullable|string|max:255',
            'qualification'=> 'nullable|string|max:255',
            'specialized_area' => 'nullable|array',
            'user_id'      => 'nullable|exists:users,id',
        ]);

        \Log::info('Update Lecturer Payload', $validated);

        // Determine the effective university_id for this update
        if (isset($validated['university_id'])) {
            $newUniId = $validated['university_id'];
            // For non-ministry/super_admin, prevent changing university
            if (!in_array($user->role, ['ministry_authority', 'super_admin'])) {
                $userUniId = currentUniversityId();
                if ($newUniId != $userUniId) {
                    return response()->json(['message' => 'You cannot change the university.'], 403);
                }
                $universityId = $userUniId;
            } else {
                $universityId = $newUniId;
            }
        } else {
            $universityId = $lecturer->university_id;
        }

        // Validate faculty belongs to this university
        if (!empty($validated['faculty_id'])) {
            $faculty = Faculty::where('id', $validated['faculty_id'])
                ->where('university_id', $universityId)
                ->first();
            if (!$faculty) {
                return response()->json(['message' => 'Invalid faculty for this university'], 422);
            }
        }

        // Validate department belongs to this university
        if (!empty($validated['department_id'])) {
            $department = Department::where('id', $validated['department_id'])
                ->where('university_id', $universityId)
                ->first();
            if (!$department) {
                return response()->json(['message' => 'Invalid department for this university'], 422);
            }
        }

        $validated['university_id'] = $universityId;
        $lecturer->update($validated);

        return response()->json($lecturer);
    }

    /**
     * Remove the specified lecturer.
     */
    public function destroy($id)
    {
        $universityId = currentUniversityId();

        $lecturer = Lecturer::findOrFail($id);

        if ($universityId) {
            $faculty = $lecturer->faculty;
            if (!$faculty || $faculty->university_id != $universityId) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        $lecturer->delete();

        return response()->json(['message' => 'Lecturer deleted']);
    }

    /**
     * Restore a soft-deleted lecturer.
     */
    public function restore($id)
    {
        $lecturer = Lecturer::withTrashed()->findOrFail($id);

        $universityId = currentUniversityId();
        if ($universityId) {
            $faculty = $lecturer->faculty;
            if (!$faculty || $faculty->university_id != $universityId) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        $lecturer->restore();

        return response()->json(['message' => 'Lecturer restored']);
    }
}