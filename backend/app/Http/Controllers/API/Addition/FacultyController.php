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
        return response()->json(
            Faculty::with('university')->latest()->get()
        );
    }

    public function store(StoreFacultyRequest $request)
    {
        $faculty = Faculty::create($request->validated());
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
        $faculty->update($request->validated());
        return response()->json($faculty);
    }

    public function destroy(Faculty $faculty)
    {
        $faculty->delete();
        return response()->json(['message' => 'Faculty soft deleted']);
    }

    public function restore($id)
    {
        $faculty = Faculty::withTrashed()->findOrFail($id);
        $faculty->restore();
        return response()->json(['message' => 'Faculty restored']);
    }

    public function forceDelete($id)
    {
        $faculty = Faculty::withTrashed()->findOrFail($id);
        $faculty->forceDelete();
        return response()->json(['message' => 'Faculty permanently deleted']);
    }
}
