<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'start_time' => ['required', 'date_format:"H:i"'],
            'end_time' => ['required', 'date_format:"H:i"','after:start_time'],
            'break_times.*.start_time' => ['nullable','date_format:"H:i"','required_with:break_times.*.end_time','after_or_equal:start_time','before:end_time'],
            'break_times.*.end_time' => ['nullable', 'date_format:"H:i"','required_with:break_times.*.start_time','after:break_times.*.start_time','before_or_equal:end_time'],
            'note' => ['required','max:255'],
        ];
    }

    public function messages()
    {
        return [
            'start_time.required' => '出勤時間を入力してください',
            'start_time.date_format' => '出勤時間は00:00の形式で入力してください',
            'end_time.required' => '退勤時間を入力してください',
            'end_time.date_format' => '退勤時間は00:00の形式で入力してください',
            'end_time.after'=> '出勤時間もしくは退勤時間が不適切な値です',
            'break_times.*.start_time.date_format' => '休憩時間は00:00の形式で入力してください',
            'break_times.*.start_time.required_with' => '休憩開始時間を入力してください',
            'break_times.*.start_time.after_or_equal'=> '休憩時間が不適切な値です',
            'break_times.*.start_time.before'=> '休憩時間が不適切な値です',
            'break_times.*.end_time.date_format' => '休憩時間は00:00の形式で入力してください',
            'break_times.*.end_time.required_with' => '休憩終了時間を入力してください',
            'break_times.*.end_time.after'=> '休憩時間が不適切な値です',
            'break_times.*.end_time.before_or_equal'=> '休憩時間もしくは退勤時間が不適切な値です',
            'note.required' => '備考を記入してください',
            'note.max' => '備考は255文字以内で入力してください',
        ];
    }
}
