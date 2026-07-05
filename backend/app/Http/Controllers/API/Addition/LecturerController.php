<?php

namespace App\Http\Controllers\API\Addition;
use App\Http\Controllers\Controller;

use App\Models\Lecturer;
use App\Http\Requests\StoreLecturerRequest;
use App\Http\Requests\UpdateLecturerRequest;

class LecturerController extends Controller
{
    public function index()
    {
        return response()->json(
            Lecturer::with('university', 'faculty', 'department')
                ->latest()
                ->get()
        );
    }

    public function store(StoreLecturerRequest $request)
    {
        $lecturer = Lecturer::create($request->validated());
        return response()->json($lecturer, 201);
    }

    public function show(Lecturer $lecturer)
    {
        return response()->json(
            $lecturer->load(
                'university',
                'faculty',
                'department',
                'academicPapers'
            )
        );
    }

    public function update(UpdateLecturerRequest $request, Lecturer $lecturer)
    {
        $lecturer->update($request->validated());
        return response()->json($lecturer);
    }

    public function destroy(Lecturer $lecturer)
    {
        $lecturer->delete();
        return response()->json(['message' => 'Lecturer soft deleted']);
    }

    public function restore($id)
    {
        $lecturer = Lecturer::withTrashed()->findOrFail($id);
        $lecturer->restore();
        return response()->json(['message' => 'Lecturer restored']);
    }

    public function forceDelete($id)
    {
        $lecturer = Lecturer::withTrashed()->findOrFail($id);
        $lecturer->forceDelete();
        return response()->json(['message' => 'Lecturer permanently deleted']);
    }

    public function forDropdown()
    {
        try {
            $lecturers = Lecturer::with(['faculty' => function($query) {
                $query->select('id', 'facultyname');
            }])
            ->select('id', 'lecturername', 'faculty_id')
            ->orderBy('lecturername')
            ->get();
            
            return response()->json([
                'success' => true,
                'data' => $lecturers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch lecturers',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
