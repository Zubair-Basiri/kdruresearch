<?php

namespace App\Http\Controllers\API\Addition;
use App\Http\Controllers\Controller;

use App\Models\Faculty;
use App\Http\Requests\StoreFacultyRequest;
use App\Http\Requests\UpdateFacultyRequest;

class FacultyController extends Controller
{
    public function index()
    {
        $query = Faculty::with('university');
        $universityId = currentUniversityId();
        if ($universityId) {
            $query->where('university_id', $universityId);
        }
        return response()->json($query->latest()->get());
    }

    public function store(StoreFacultyRequest $request)
    {
        $data = $request->validated();
        $universityId = currentUniversityId();
        if ($universityId) {
            $data['university_id'] = $universityId;
        }
        $faculty = Faculty::create($data);
        return response()->json($faculty, 201);
    }

    public function show(Faculty $faculty)
    {
        return response()->json(
            $faculty->load('university', 'departments')
        );
    }

    public function update(UpdateFacultyRequest $request, Faculty $faculty)
    {
        $universityId = currentUniversityId();
        if ($universityId && $department->university_id != $universityId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $faculty->update($request->validated());
        return response()->json($faculty);
    }

    public function destroy(Faculty $faculty)
    {
        $universityId = currentUniversityId();
        if ($universityId && $department->university_id != $universityId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $faculty->delete();
        return response()->json(['message' => 'Faculty soft deleted']);
    }

    public function restore($id)
    {
        $universityId = currentUniversityId();
        if ($universityId && $department->university_id != $universityId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $faculty = Faculty::withTrashed()->findOrFail($id);
        $faculty->restore();
        return response()->json(['message' => 'Faculty restored']);
    }

    public function forceDelete($id)
    {
        $universityId = currentUniversityId();
        if ($universityId && $department->university_id != $universityId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $faculty = Faculty::withTrashed()->findOrFail($id);
        $faculty->forceDelete();
        return response()->json(['message' => 'Faculty permanently deleted']);
    }
}
