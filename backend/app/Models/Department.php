<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['deptname', 'faculty_id', 'university_id'];

    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }

    public function university()
    {
        return $this->belongsTo(University::class);
    }

    public function lecturers()
    {
        return $this->hasMany(Lecturer::class);
    }
}
