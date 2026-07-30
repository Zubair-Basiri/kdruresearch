<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LecturerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'father_name', 'code_no', 'status',
        'faculty_id', 'department_id', 'academic_grade', 'qualification',
        'course', 'domestic_international',
        'academic_grade_entrence_date', 'promotion_date'
    ];

    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function promotionHistories()
    {
        return $this->hasMany(PromotionHistory::class);
    }
}
