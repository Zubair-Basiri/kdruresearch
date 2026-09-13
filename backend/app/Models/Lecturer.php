<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lecturer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'lecturername',
        'university_id',
        'faculty_id',
        'department_id',
        'grade',
        'qualification',
        'specialized_area',
        'user_id',
        'exclude_from_top_researchers',
    ];

    protected $casts = [
        'specialized_area' => 'array',
        'exclude_from_top_researchers' => 'boolean',
    ];

    public function university() { return $this->belongsTo(University::class); }
    public function faculty() { return $this->belongsTo(Faculty::class); }
    public function department() { return $this->belongsTo(Department::class); }
    public function academicPapers() { return $this->hasMany(AcademicPaper::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function submittedPapers() { return $this->hasMany(SubmittedPaper::class); }
}
