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
        return response()->json(
            Department::with('university', 'faculty')->latest()->get()
        );
    }

    public function store(StoreDepartmentRequest $request)
    {
        $department = Department::create($request->validated());
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
        $department->update($request->validated());
        return response()->json($department);
    }

    public function destroy(Department $department)
    {
        $department->delete();
        return response()->json(['message' => 'Department soft deleted']);
    }

    public function restore($id)
    {
        $department = Department::withTrashed()->findOrFail($id);
        $department->restore();
        return response()->json(['message' => 'Department restored']);
    }

    public function forceDelete($id)
    {
        $department = Department::withTrashed()->findOrFail($id);
        $department->forceDelete();
        return response()->json(['message' => 'Department permanently deleted']);
    }
}
