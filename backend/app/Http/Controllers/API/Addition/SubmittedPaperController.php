<?php

namespace App\Http\Controllers\API\Addition;

use App\Http\Controllers\Controller;
use App\Models\SubmittedPaper;
use App\Models\AcademicPaper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SubmittedPaperController extends Controller
{
    // List submissions (lecturer sees own; admin sees all)
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = SubmittedPaper::with('lecturer');

        if ($user->role === 'user') {
            $lecturerId = $user->lecturer?->id;
            if (!$lecturerId) {
                return response()->json([]);
            }
            $query->where('lecturer_id', $lecturerId);
        }

        // Filter by approval_status (pending, approved, rejected)
        if ($request->has('approval_status') && in_array($request->approval_status, ['pending', 'approved', 'rejected'])) {
            $query->where('approval_status', $request->approval_status);
        }

        return response()->json($query->latest()->get());
    }

    // Store new submission (only lecturers)
    public function store(Request $request)
    {
        $user = Auth::user();

        // Allow user, admin, and super_admin
        if (!in_array($user->role, ['user', 'admin', 'super_admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Determine lecturer_id
        if ($user->role === 'user') {
            // Lecturer: use their own lecturer record
            $lecturerId = $user->lecturer?->id;
            if (!$lecturerId) {
                return response()->json(['message' => 'No lecturer profile found.'], 400);
            }
        } else {
            // Admin / super_admin: must provide a lecturer_id
            $validated = $request->validate([
                'lecturer_id' => 'required|exists:lecturers,id',
            ]);
            $lecturerId = $validated['lecturer_id'];
        }

        // Validate the rest of the fields
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'year' => 'required|integer|min:1900|max:' . date('Y'),
            'publication' => 'required|string',
            'indexed' => 'nullable|string',
            'citation' => 'nullable|integer|min:0',
            'funding' => 'nullable|string',
            'collaboration' => 'nullable|string',
            'language' => 'nullable|string',
            'author_position' => 'nullable|string',
            'status' => 'nullable|string|in:Published,Proposal Stage,Ongoing Research',
            'paper_link' => 'nullable|url|max:2000',
        ]);

        $submission = SubmittedPaper::create([
            'lecturer_id' => $lecturerId,
            'approval_status' => 'pending',
            ...$validated
        ]);

        return response()->json($submission, 201);
    }

    // Show single submission
    public function show(SubmittedPaper $submittedPaper)
    {
        $this->authorizeAccess($submittedPaper);
        return response()->json($submittedPaper->load('lecturer'));
    }

    // Update (only if approval_status is pending)
    public function update(Request $request, SubmittedPaper $submittedPaper)
    {
        $this->authorizeAccess($submittedPaper);

        if ($submittedPaper->approval_status !== 'pending') {
            return response()->json(['message' => 'Cannot edit approved/rejected submissions.'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'year' => 'sometimes|integer|min:1900|max:' . date('Y'),
            'publication' => 'sometimes|string',
            'indexed' => 'nullable|string',
            'citation' => 'nullable|integer|min:0',
            'funding' => 'nullable|string',
            'collaboration' => 'nullable|string',
            'language' => 'nullable|string',
            'author_position' => 'nullable|string',
            'status' => 'nullable|string|in:Published,Proposal Stage,Ongoing Research',
            'paper_link' => 'nullable|url|max:2000',
        ]);

        $submittedPaper->update($validated);
        return response()->json($submittedPaper);
    }

    // Delete (only if approval_status is pending)
    public function destroy(SubmittedPaper $submittedPaper)
    {
        $this->authorizeAccess($submittedPaper);

        if ($submittedPaper->approval_status !== 'pending') {
            return response()->json(['message' => 'Cannot delete approved/rejected submissions.'], 403);
        }

        $submittedPaper->delete();
        return response()->json(['message' => 'Submission deleted']);
    }

    // Admin: approve and move to academic_papers
    public function approve(Request $request, SubmittedPaper $submittedPaper)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'super_admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($submittedPaper->approval_status !== 'pending') {
            return response()->json(['message' => 'Submission already processed'], 400);
        }

        $request->validate([
            'admin_comment' => 'nullable|string'
        ]);

        DB::transaction(function () use ($submittedPaper, $request, $user) {
            // Move to academic_papers
            AcademicPaper::create([
                'title' => $submittedPaper->title,
                'lecturer_id' => $submittedPaper->lecturer_id,
                'year' => $submittedPaper->year,
                'publication' => $submittedPaper->publication,
                'indexed' => $submittedPaper->indexed,
                'citation' => $submittedPaper->citation,
                'funding' => $submittedPaper->funding,
                'collaboration' => $submittedPaper->collaboration,
                'language' => $submittedPaper->language,
                'status' => $submittedPaper->status,   // publication status
                'author_position' => $submittedPaper->author_position,
            ]);

            $submittedPaper->update([
                'approval_status' => 'approved',
                'admin_comment' => $request->admin_comment,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);
        });

        return response()->json(['message' => 'Paper approved and moved to publications']);
    }

    // Admin: reject with comment
    public function reject(Request $request, SubmittedPaper $submittedPaper)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'super_admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($submittedPaper->approval_status !== 'pending') {
            return response()->json(['message' => 'Submission already processed'], 400);
        }

        $request->validate([
            'admin_comment' => 'required|string'
        ]);

        $submittedPaper->update([
            'approval_status' => 'rejected',
            'admin_comment' => $request->admin_comment,
            'approved_by' => $user->id,
        ]);

        return response()->json(['message' => 'Paper rejected']);
    }

    // Admin: update comment (only if pending)
    public function updateComment(Request $request, SubmittedPaper $submittedPaper)
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'super_admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($submittedPaper->approval_status !== 'pending') {
            return response()->json(['message' => 'Cannot edit comment after approval/rejection'], 403);
        }

        $validated = $request->validate([
            'admin_comment' => 'nullable|string'
        ]);
        $submittedPaper->update($validated);
        return response()->json(['message' => 'Comment updated']);
    }

    // Helper to check access
    private function authorizeAccess(SubmittedPaper $submission)
    {
        $user = Auth::user();
        if ($user->role !== 'user') return;
        if ($user->lecturer?->id !== $submission->lecturer_id) {
            abort(403, 'Unauthorized');
        }
    }
}