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

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/users', [AuthController::class, 'register']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::get('/sanctum/csrf-cookie', function () {
    return response()->json(['message' => 'CSRF cookie set']);
});
Route::get('lecturers-for-dropdown', [LecturerController::class, 'forDropdown']);

// Routes for all authenticated users (including lecturers)
Route::middleware(['auth:sanctum'])->group(function () {
    // User info & logout
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/user/profile', [UserController::class, 'updateProfile']);

    // Analytics (all authenticated users)
    Route::get('/top-researchers', [TopResearchersController::class, 'index']);
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
    Route::get('/dashboard/data', [FacultyComponentController::class, 'getData']);
    Route::get('/dashboard/filters', [FacultyComponentController::class, 'getFilters']);

    Route::get('/submitted-papers', [SubmittedPaperController::class, 'index']);
    Route::get('/submitted-papers/{submittedPaper}', [SubmittedPaperController::class, 'show']);
    Route::apiResource('academic-papers', AcademicPaperController::class);
    Route::post('academic-papers/{id}/restore', [AcademicPaperController::class, 'restore']);

    Route::get('/user/theme/load', [UserController::class, 'loadThemeSettings']);
    Route::post('/user/theme/save', [UserController::class, 'saveThemeSettings']);
});

// Admin & Super Admin only routes
Route::middleware(['auth:sanctum', 'role:admin,super_admin'])->group(function () {
    // User management
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    // Route::put('/users/{id}/approve', [UserController::class, 'approve']);
    Route::put('/users/{id}/toggle-approval', [UserController::class, 'toggleApproval']);

    // Data management
    Route::apiResource('universities', UniversityController::class);
    Route::post('universities/{id}/restore', [UniversityController::class, 'restore']);
    Route::delete('universities/{id}/force-delete', [UniversityController::class, 'forceDelete']);

    Route::apiResource('faculties', FacultyController::class);
    Route::post('faculties/{id}/restore', [FacultyController::class, 'restore']);
    Route::delete('faculties/{id}/force-delete', [FacultyController::class, 'forceDelete']);

    Route::apiResource('departments', DepartmentController::class);
    Route::post('departments/{id}/restore', [DepartmentController::class, 'restore']);
    Route::delete('departments/{id}/force-delete', [DepartmentController::class, 'forceDelete']);

    Route::apiResource('lecturers', LecturerController::class);
    Route::post('lecturers/{id}/restore', [LecturerController::class, 'restore']);
    Route::delete('lecturers/{id}/force-delete', [LecturerController::class, 'forceDelete']);
    
    Route::delete('academic-papers/{id}/force-delete', [AcademicPaperController::class, 'forceDelete']);
    Route::put('/submitted-papers/{submittedPaper}/comment', [SubmittedPaperController::class, 'updateComment']);

    Route::get('/key-findings/filters', [KeyFindingsController::class, 'filters']);
    Route::get('/key-findings', [KeyFindingsController::class, 'index']);
    Route::get('/key-findings/preview', [KeyFindingsController::class, 'previewPdf']);
});

Route::middleware(['auth:sanctum', 'role:admin,super_admin'])->group(function () {
    Route::put('/submitted-papers/{submittedPaper}/approve', [SubmittedPaperController::class, 'approve']);
    Route::put('/submitted-papers/{submittedPaper}/reject', [SubmittedPaperController::class, 'reject']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/submitted-papers', [SubmittedPaperController::class, 'store']);
    Route::put('/submitted-papers/{submittedPaper}', [SubmittedPaperController::class, 'update']);
    Route::delete('/submitted-papers/{submittedPaper}', [SubmittedPaperController::class, 'destroy']);
});