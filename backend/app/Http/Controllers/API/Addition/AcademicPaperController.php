<?php

namespace App\Http\Controllers\API\Addition;
use App\Http\Controllers\Controller;

use App\Models\AcademicPaper;
use App\Http\Requests\StoreAcademicPaperRequest;
use App\Http\Requests\UpdateAcademicPaperRequest;
use Illuminate\Support\Facades\Auth;

class AcademicPaperController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $query = AcademicPaper::with('lecturer');

        if ($user->role === 'user') {
            $lecturerId = $user->lecturer?->id;  // now works
            if ($lecturerId) {
                $query->where('lecturer_id', $lecturerId);
            } else {
                return response()->json([]);
            }
        }

        return response()->json($query->latest()->get());
    }

    public function store(StoreAcademicPaperRequest $request)
    {
        $paper = AcademicPaper::create($request->validated());
        return response()->json($paper, 201);
    }

    public function show(AcademicPaper $academicPaper)
    {
        return response()->json(
            $academicPaper->load('lecturer')
        );
    }

    public function update(UpdateAcademicPaperRequest $request, AcademicPaper $academicPaper)
    {
        $academicPaper->update($request->validated());
        return response()->json($academicPaper);
    }

    public function destroy(AcademicPaper $academicPaper)
    {
        $academicPaper->delete();
        return response()->json(['message' => 'Paper soft deleted']);
    }

    public function restore($id)
    {
        $paper = AcademicPaper::withTrashed()->findOrFail($id);
        $paper->restore();
        return response()->json(['message' => 'Paper restored']);
    }

    public function forceDelete($id)
    {
        $paper = AcademicPaper::withTrashed()->findOrFail($id);
        $paper->forceDelete();
        return response()->json(['message' => 'Paper permanently deleted']);
    }
}
