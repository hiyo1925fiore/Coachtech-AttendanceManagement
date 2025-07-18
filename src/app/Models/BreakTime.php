<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakTime extends Model
{
    use HasFactory;

    protected $guarded = [
        'id',
    ];

    protected $fillable = [
        'attendance_id',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function breakTimeRequests()
    {
        return $this->hasMany(BreakTimeRequest::class);
    }

    // 休憩時間を秒単位で取得
    public function getDurationAttribute()
    {
        if (!$this->start_time || !$this->end_time) {
            return null;
        }

        return $this->end_time->diffInSeconds($this->start_time);
    }

    // 休憩時間を時間:分の形式で取得
    public function getFormattedDurationAttribute()
    {
        $seconds = $this->duration;
        if ($seconds === null) {
            return null;
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return sprintf('%02d:%02d', $hours, $minutes);
    }
}
