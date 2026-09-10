<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Addition\UniversityController;
use App\Http\Controllers\API\Addition\FacultyController;
use App\Http\Controllers\API\Addition\DepartmentController;
use App\Http\Controllers\API\Addition\LecturerController;
use App\Http\Controllers\API\Addition\AcademicPaperController;
use App\Http\Controllers\API\GradeSummaryController;
use App\Http\Controllers\API\YearSummaryController;
use App\Http\Controllers\API\FacultySummaryController;
use App\Http\Controllers\API\FacultyAggregationController;
use App\Http\Controllers\API\FacultyComponentController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\TopResearchersController;
use App\Http\Controllers\API\KeyFindingsController;
use App\Http\Controllers\API\Addition\SubmittedPaperController;
use App\Http\Controllers\API\CitationAnalyticsController;
use App\Http\Controllers\API\BenchmarkingController;
use App\Http\Controllers\API\ResearchAreaAnalyticsController;
use App\Http\Controllers\API\CollaborationAnalyticsController;
use App\Http\Controllers\API\Top10Controller;
use App\Http\Controllers\API\ResearchForecastingController;
use Illuminate\Support\Facades\DB;

// Public routes
Route::get('/health', function () {
    DB::select('SELECT 1');

    return response()->json(['status' => 'ok']);
});
Route::post('/login', [AuthController::class, 'login']);
Route::post('/users', [AuthController::class, 'register']);
Route::get('/sanctum/csrf-cookie', function () {
    return response()->json(['message' => 'CSRF cookie set']);
});
Route::get('lecturers-for-dropdown', [LecturerController::class, 'forDropdown']);
Route::post('/guest-login', [AuthController::class, 'guestLogin']);
Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail']);
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');

// Routes for all authenticated users (including lecturers and guests)
Route::middleware(['auth:sanctum'])->group(function () {
    // User info & logout
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/user/profile', [UserController::class, 'updateProfile']);

    // Analytics (all authenticated users)
    Route::get('/top-researchers', [TopResearchersController::class, 'index']);
    Route::get('/researcher-papers/{id}', [TopResearchersController::class, 'getResearcherPapers']);
    Route::get('/top-researchers/preview', [TopResearchersController::class, 'previewPdf']);
    Route::get('/year-summary', [YearSummaryController::class, 'index']);
    Route::get('/category-summary', [YearSummaryController::class, 'category']);
    Route::get('/year-summary/preview', [YearSummaryController::class, 'previewPdf']);
    Route::get('/grade-summary', [GradeSummaryController::class, 'index']);
    Route::get('/grade-summary/preview', [GradeSummaryController::class, 'previewPdf']);
    Route::get('/grade-summary/download', [GradeSummaryController::class, 'downloadPdf']);
    Route::get('/faculty-summary', [FacultySummaryController::class, 'index']);
    Route::get('/faculty-summary/preview', [FacultySummaryController::class, 'previewPdf']);
    Route::get('/faculty-aggregation', [FacultyAggregationController::class, 'index']);
    
    // Dashboard routes – guest must have university
    Route::middleware(['guest.has.university'])->group(function () {
        Route::get('/dashboard/data', [FacultyComponentController::class, 'getData']);
        Route::get('/dashboard/filters', [FacultyComponentController::class, 'getFilters']);
    });
    
    Route::put('/user/university', [AuthController::class, 'updateUniversity']);
    Route::get('/submitted-papers', [SubmittedPaperController::class, 'index']);
    Route::get('/submitted-papers/{submittedPaper}', [SubmittedPaperController::class, 'show']);
    Route::apiResource('academic-papers', AcademicPaperController::class);
    Route::post('academic-papers/{id}/restore', [AcademicPaperController::class, 'restore']);
    Route::get('/user/theme/load', [UserController::class, 'loadThemeSettings']);
    Route::post('/user/theme/save', [UserController::class, 'saveThemeSettings']);

    // ===== READ‑ONLY GET ROUTES (NO ROLE RESTRICTION) =====
    Route::get('/faculties', [FacultyController::class, 'index']);
    Route::get('/faculties/{faculty}', [FacultyController::class, 'show']);
    Route::get('/departments', [DepartmentController::class, 'index']);
    Route::get('/departments/{department}', [DepartmentController::class, 'show']);
    
    // Universities list – accessible to all authenticated users (including guests)
    Route::get('/universities', [UniversityController::class, 'index']);
    // Shared filter metadata is read-only and is required by all analytics
    // pages, not only administrator-only Key Findings screens.
    Route::get('/key-findings/filters', [KeyFindingsController::class, 'filters']);
    Route::get('/analytics/citations', [CitationAnalyticsController::class, 'index']);
    Route::get('/analytics/benchmarking', [BenchmarkingController::class, 'index']);
    Route::get('/analytics/research-areas', [ResearchAreaAnalyticsController::class, 'index']);
    Route::get('/research-areas', [ResearchAreaAnalyticsController::class, 'areas']);
    Route::get('/analytics/collaboration', [CollaborationAnalyticsController::class, 'index']);
    Route::get('/collaboration-types', [CollaborationAnalyticsController::class, 'collaborationTypes']);
    Route::get('/analytics/top-10', [Top10Controller::class, 'index']);
    Route::get('/analytics/research-forecasting', [ResearchForecastingController::class, 'index']);
    Route::get('/analytics/citation/preview', [CitationAnalyticsController::class, 'previewPdf']);
    Route::get('/analytics/benchmarking/preview', [BenchmarkingController::class, 'previewPdf']);
    Route::get('/analytics/research-areas/preview', [ResearchAreaAnalyticsController::class, 'previewPdf']);
    Route::get('/analytics/collaboration/preview', [CollaborationAnalyticsController::class, 'previewPdf']);
    Route::get('/analytics/top-10/preview', [Top10Controller::class, 'previewPdf']);
    Route::get('/analytics/research-forecasting/preview', [ResearchForecastingController::class, 'previewPdf']);
});

// ===== ADMIN ROUTES FOR UNIVERSITY CRUD (only ministry_authority) =====
Route::middleware(['auth:sanctum', 'role:ministry_authority'])->group(function () {
    Route::post('/universities', [UniversityController::class, 'store']);
    Route::put('/universities/{id}', [UniversityController::class, 'update']);
    Route::delete('/universities/{id}', [UniversityController::class, 'destroy']);
    Route::post('/universities/{id}/restore', [UniversityController::class, 'restore']);
    Route::delete('/universities/{id}/force-delete', [UniversityController::class, 'forceDelete']);
});

// ===== OTHER ADMIN ROUTES =====
Route::middleware(['auth:sanctum', 'role:ministry_authority,admin,super_admin'])->group(function () {
    // User management
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    Route::put('/users/{id}/toggle-approval', [UserController::class, 'toggleApproval']);

    // Data management – exclude GET methods to avoid overriding the public ones
    Route::post('/faculties', [FacultyController::class, 'store']);
    Route::put('/faculties/{faculty}', [FacultyController::class, 'update']);
    Route::delete('/faculties/{faculty}', [FacultyController::class, 'destroy']);
    Route::post('/faculties/{id}/restore', [FacultyController::class, 'restore']);
    Route::delete('/faculties/{id}/force-delete', [FacultyController::class, 'forceDelete']);

    Route::post('/departments', [DepartmentController::class, 'store']);
    Route::put('/departments/{department}', [DepartmentController::class, 'update']);
    Route::delete('/departments/{department}', [DepartmentController::class, 'destroy']);
    Route::post('/departments/{id}/restore', [DepartmentController::class, 'restore']);
    Route::delete('/departments/{id}/force-delete', [DepartmentController::class, 'forceDelete']);

    Route::apiResource('lecturers', LecturerController::class);
    Route::post('lecturers/{id}/restore', [LecturerController::class, 'restore']);
    Route::delete('lecturers/{id}/force-delete', [LecturerController::class, 'forceDelete']);
    
    Route::delete('academic-papers/{id}/force-delete', [AcademicPaperController::class, 'forceDelete']);
    Route::put('/submitted-papers/{submittedPaper}/comment', [SubmittedPaperController::class, 'updateComment']);

    Route::get('/key-findings', [KeyFindingsController::class, 'index']);
    Route::get('/key-findings/preview', [KeyFindingsController::class, 'previewPdf']);
});

Route::middleware(['auth:sanctum', 'role:ministry_authority,admin,super_admin'])->group(function () {
    Route::put('/submitted-papers/{submittedPaper}/approve', [SubmittedPaperController::class, 'approve']);
    Route::put('/submitted-papers/{submittedPaper}/reject', [SubmittedPaperController::class, 'reject']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/submitted-papers', [SubmittedPaperController::class, 'store']);
    Route::put('/submitted-papers/{submittedPaper}', [SubmittedPaperController::class, 'update']);
    Route::delete('/submitted-papers/{submittedPaper}', [SubmittedPaperController::class, 'destroy']);
});

// Lecturer Profiles
Route::middleware(['auth:sanctum', 'role:ministry_authority,super_admin,admin_admin,lecturer_profile_admin'])->group(function () {
    Route::get('lecturer-profiles/export-pdf', [\App\Http\Controllers\API\Addition\LecturerProfileController::class, 'exportPdf']);
    Route::apiResource('lecturer-profiles', \App\Http\Controllers\API\Addition\LecturerProfileController::class);
});
