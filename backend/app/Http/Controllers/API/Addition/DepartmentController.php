<?php

namespace App\Http\Controllers\API\Addition;
use App\Http\Controllers\Controller;

use App\Models\Department;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;

class DepartmentController extends Controller
{
    public function index()
    {
        $query = Department::with('university', 'faculty');
        $universityId = currentUniversityId();
        if ($universityId) {
            $query->where('university_id', $universityId);
        }
        return response()->json($query->latest()->get());
    }

    public function store(StoreDepartmentRequest $request)
    {
        $data = $request->validated();
        $universityId = currentUniversityId();
        if ($universityId) {
            $data['university_id'] = $universityId;
            // Validate faculty belongs to this university
            if (isset($data['faculty_id'])) {
                $faculty = Faculty::where('id', $data['faculty_id'])->where('university_id', $universityId)->first();
                if (!$faculty) {
                    return response()->json(['message' => 'Invalid faculty for this university'], 422);
                }
            }
        }
        $department = Department::create($data);
        return response()->json($department, 201);
    }

    public function show(Department $department)
    {
        return response()->json(
            $department->load('university', 'faculty', 'lecturers')
        );
    }

    public function update(UpdateDepartmentRequest $request, Department $department)
    {
        $universityId = currentUniversityId();
        if ($universityId && $department->university_id != $universityId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $department->update($request->validated());
        return response()->json($department);
    }

    public function destroy(Department $department)
    {
        $universityId = currentUniversityId();
        if ($universityId && $department->university_id != $universityId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $department->delete();
        return response()->json(['message' => 'Department soft deleted']);
    }

    public function restore($id)
    {
        $universityId = currentUniversityId();
        if ($universityId && $department->university_id != $universityId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $department = Department::withTrashed()->findOrFail($id);
        $department->restore();
        return response()->json(['message' => 'Department restored']);
    }

    public function forceDelete($id)
    {
        $universityId = currentUniversityId();
        if ($universityId && $department->university_id != $universityId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $department = Department::withTrashed()->findOrFail($id);
        $department->forceDelete();
        return response()->json(['message' => 'Department permanently deleted']);
    }
}
