<?php

namespace App\Http\Controllers\API\Addition;
use App\Http\Controllers\Controller;

use App\Models\University;
use App\Http\Requests\StoreUniversityRequest;
use App\Http\Requests\UpdateUniversityRequest;

class UniversityController extends Controller
{
    public function index()
    {
        $universities = University::with('faculties')->latest()->get();
        return response()->json($universities);
    }

    public function store(StoreUniversityRequest $request)
    {
        $university = University::create($request->validated());
        return response()->json($university, 201);
    }

    public function show(University $university)
    {
        return response()->json(
            $university->load('faculties.departments')
        );
    }

    public function update(UpdateUniversityRequest $request, University $university)
    {
        $university->update($request->validated());
        return response()->json($university);
    }

    public function destroy(University $university)
    {
        $university->delete();
        return response()->json(['message' => 'University soft deleted']);
    }

    public function restore($id)
    {
        $university = University::withTrashed()->findOrFail($id);
        $university->restore();
        return response()->json(['message' => 'University restored']);
    }

    public function forceDelete($id)
    {
        $university = University::withTrashed()->findOrFail($id);
        $university->forceDelete();
        return response()->json(['message' => 'University permanently deleted']);
    }
}

