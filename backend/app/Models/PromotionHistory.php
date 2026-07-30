<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'lecturer_profile_id', 'from_grade', 'to_grade', 'promotion_date', 'notes'
    ];

    public function lecturerProfile()
    {
        return $this->belongsTo(LecturerProfile::class);
    }
}
