<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSession;
use App\Models\ClassRoom;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $totalClasses = ClassRoom::count();
        $totalStudents = Student::count();
        $sessionsToday = AttendanceSession::whereDate('session_date', today())->count();

        $avgAttendance = DB::table('attendance_records')
            ->selectRaw("AVG(CASE
                WHEN status = 'present'  THEN 100
                WHEN status = 'late'     THEN 80
                WHEN status = 'absent'   THEN 0
                WHEN status = 'excused'  THEN 100
                ELSE 0
            END) as avg_score")
            ->value('avg_score');

        $poorAttendanceStudents = DB::table('students')
            ->join('class_room_student', 'students.id', '=', 'class_room_student.student_id')
            ->join('class_rooms', 'class_room_student.class_room_id', '=', 'class_rooms.id')
            ->join('attendance_sessions', 'attendance_sessions.class_room_id', '=', 'class_rooms.id')
            ->join('attendance_records', function ($join) {
                $join->on('attendance_records.student_id', '=', 'students.id')
                    ->on('attendance_records.attendance_session_id', '=', 'attendance_sessions.id');
            })
            ->select(
                'students.id',
                'students.student_number',
                'students.fullname',
                'class_rooms.id as class_room_id',
                'class_rooms.name as class_name',
                'class_rooms.section as class_section',
                DB::raw("COUNT(attendance_records.id) as total_sessions"),
                DB::raw("SUM(CASE WHEN attendance_records.status = 'absent' THEN 1 ELSE 0 END) as absent_count"),
                DB::raw("AVG(CASE
                    WHEN attendance_records.status = 'present'  THEN 100
                    WHEN attendance_records.status = 'late'     THEN 80
                    WHEN attendance_records.status = 'absent'   THEN 0
                    WHEN attendance_records.status = 'excused'  THEN 100
                    ELSE 0
                END) as average_score")
            )
            ->groupBy('students.id', 'students.student_number', 'students.fullname', 'class_rooms.id', 'class_rooms.name', 'class_rooms.section')
            ->having('average_score', '<', 75)
            ->orderBy('average_score', 'asc')
            ->limit(20)
            ->get();

        $recentSessions = AttendanceSession::with('classRoom')
            ->orderByDesc('session_date')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'totalClasses',
            'totalStudents',
            'sessionsToday',
            'avgAttendance',
            'poorAttendanceStudents',
            'recentSessions',
        ));
    }
}
