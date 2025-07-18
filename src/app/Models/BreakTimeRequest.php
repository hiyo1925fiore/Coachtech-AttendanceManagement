<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakTimeRequest extends Model
{
    use HasFactory;

    protected $guarded = [
        'id',
    ];

    protected $fillable = [
        'attendance_id',
        'break_time_id',
        'start_time',
        'end_time',
    ];

    public function breakTime()
    {
        return $this->belongsTo(BreakTime::class);
    }

    public function attendanceRequest()
    {
        return $this->belongsTo(AttendanceRequest::class);
    }

}
