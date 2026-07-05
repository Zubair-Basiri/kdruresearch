<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicPaper extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title', 'lecturer_id', 'year', 'publication', 'indexed',
        'citation', 'funding', 'collaboration', 'language', 'status', 'author_position'
    ];

    public function lecturer() { return $this->belongsTo(Lecturer::class); }
}
