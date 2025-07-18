<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $guarded = [
        'id',
    ];

    protected $fillable = [
        'user_id',
        'date',
        'start_time',
        'end_time',
        'note',
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breakTimes()
    {
        return $this->hasMany(BreakTime::class);
    }

    public function attendanceRequests()
    {
        return $this->hasMany(AttendanceRequest::class);
    }

    // 勤務時間を計算（休憩時間を除く）
    public function getWorkingTimeAttribute()
    {
        if (!$this->start_time || !$this->end_time) {
            return null;
        }

        $workingSeconds = $this->end_time->diffInSeconds($this->start_time);
        
        // 休憩時間を減算
        $breakSeconds = $this->breakTimes->sum(function ($breakTime) {
            if ($breakTime->start_time && $breakTime->end_time) {
                return $breakTime->end_time->diffInSeconds($breakTime->start_time);
            }
            return 0;
        });

        return $workingSeconds - $breakSeconds;
    }

    // 勤務時間を時間:分の形式で取得
    public function getFormattedWorkingTimeAttribute()
    {
        $seconds = $this->working_time;
        if ($seconds === null) {
            return null;
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return sprintf('%02d:%02d', $hours, $minutes);
    }
}
