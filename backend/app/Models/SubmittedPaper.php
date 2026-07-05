<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubmittedPaper extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'lecturer_id', 'title', 'year', 'publication', 'indexed', 'citation',
        'funding', 'collaboration', 'language', 'status', 'approval_status', 'author_position',
        'admin_comment', 'approved_by', 'approved_at'
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function lecturer()
    {
        return $this->belongsTo(Lecturer::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scope for pending submissions
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}