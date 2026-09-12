<?php

namespace App\Http\Controllers\API\Addition;
use App\Http\Controllers\Controller;
use App\Services\PaperDuplicateService;
use App\Models\AcademicPaper;
use App\Models\Lecturer;
use App\Http\Requests\StoreAcademicPaperRequest;
use App\Http\Requests\UpdateAcademicPaperRequest;
use Illuminate\Support\Facades\Auth;

class AcademicPaperController extends Controller
{
    protected PaperDuplicateService $duplicateService;

    public function __construct(PaperDuplicateService $duplicateService)
    {
        $this->duplicateService = $duplicateService;
    }
    public function index()
    {
        $user = Auth::user();
        $query = AcademicPaper::with('lecturer');
        $universityId = currentUniversityId();
        if ($universityId) {
            $query->whereHas('lecturer', function ($q) use ($universityId) {
                $q->where('university_id', $universityId);
            });
        }
        // Existing lecturer role filter (if any) should also apply
        if ($user->role === 'user') {
            $lecturerId = $user->lecturer?->id;
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
        $data = $request->validated();

        $universityId = currentUniversityId();
        if ($universityId) {
            $lecturer = Lecturer::where('id', $data['lecturer_id'])
                                ->where('university_id', $universityId)
                                ->first();
            if (!$lecturer) {
                return response()->json(['message' => 'Invalid lecturer for this university'], 422);
            }
        }

        // Duplicate check
        $result = $this->duplicateService->findDuplicate(
            $data['title'],
            $data['author_position'] ?? null,
            $data['language'] ?? null
        );
        if ($result) {
            return $this->duplicateResponse($result);
        }

        $paper = AcademicPaper::create($data);
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
        $data = $request->validated();

        $universityId = currentUniversityId();
        if ($universityId) {
            $lecturer = $academicPaper->lecturer;
            if (!$lecturer || $lecturer->university_id != $universityId) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        if (
    isset($data['title']) &&
    $data['title'] !== $academicPaper->title &&
    $this->duplicateService->isSubstantiallyDifferent($academicPaper->title, $data['title'])
) {
    $title = $data['title'];
    $authorPos = $data['author_position'] ?? $academicPaper->author_position;
    $language = $data['language'] ?? $academicPaper->language;
    // Exclude the current academic paper from the check
    $result = $this->duplicateService->findDuplicate($title, $authorPos, $language, $academicPaper->id, 'academic');
    if ($result) {
        return $this->duplicateResponse($result);
    }
}

        $academicPaper->update($data);
        return response()->json($academicPaper);
    }

    public function destroy(AcademicPaper $academicPaper)
    {
        $academicPaper->forceDelete();
        return response()->json(['message' => 'Paper permanently deleted']);
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

    protected function duplicateResponse(array $result): \Illuminate\Http\JsonResponse
    {
        $messages = [
            'exact' => 'A paper with this exact title already exists for this author position.',
            'same_language' => 'A very similar paper already exists for this author position.',
            'cross_language' => 'A semantically equivalent paper already exists in another language for this author position.',
        ];

        $errorMessages = [
            'exact' => 'This title already exists for this author position.',
            'same_language' => 'A very similar title already exists for this author position.',
            'cross_language' => 'This paper appears to be a duplicate of an existing paper in another language.',
        ];

        $type = $result['duplicate_type'];
        $message = $messages[$type] ?? 'A duplicate paper was found.';
        $errorMessage = $errorMessages[$type] ?? 'Duplicate paper detected.';

        return response()->json([
            'message' => $message,
            'duplicate' => true,
            'duplicate_type' => $type,
            'similarity' => $result['similarity'],
            'matched_paper_id' => $result['matched_paper_id'],
            'matched_table' => $result['matched_table'],
            'matched_title' => $result['matched_title'],
            'matched_author_position' => $result['matched_author_position'],
            'errors' => [
                'title' => [$errorMessage],
            ],
        ], 422);
    }
}
