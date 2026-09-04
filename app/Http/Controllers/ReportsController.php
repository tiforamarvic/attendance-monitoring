<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportsController extends Controller
{
    public function index(Request $request): View
    {
        $classRooms = ClassRoom::orderBy('name')->get(['id', 'name', 'section']);

        $selectedClassId = $request->input('class_id');
        $threshold = (int) $request->input('threshold', 75);

        $query = DB::table('students')
            ->join('class_room_student', 'students.id', '=', 'class_room_student.student_id')
            ->join('class_rooms', 'class_room_student.class_room_id', '=', 'class_rooms.id')
            ->join('attendance_sessions', 'attendance_sessions.class_room_id', '=', 'class_rooms.id')
            ->join('attendance_records', function ($join) {
                $join->on('attendance_records.student_id', '=', 'students.id')
                    ->on('attendance_records.attendance_session_id', '=', 'attendance_sessions.id');
            })
            ->select(
                'students.id as student_id',
                'students.student_number',
                'students.fullname',
                'class_rooms.id as class_room_id',
                'class_rooms.name as class_name',
                'class_rooms.section as class_section',
                DB::raw('COUNT(attendance_records.id) as total_sessions'),
                DB::raw("SUM(CASE WHEN attendance_records.status = 'present' THEN 1 ELSE 0 END) as present_count"),
                DB::raw("SUM(CASE WHEN attendance_records.status = 'late' THEN 1 ELSE 0 END) as late_count"),
                DB::raw("SUM(CASE WHEN attendance_records.status = 'absent' THEN 1 ELSE 0 END) as absent_count"),
                DB::raw("SUM(CASE WHEN attendance_records.status = 'excused' THEN 1 ELSE 0 END) as excused_count"),
                DB::raw("ROUND(AVG(CASE
                    WHEN attendance_records.status = 'present' THEN 100
                    WHEN attendance_records.status = 'late'    THEN 80
                    WHEN attendance_records.status = 'absent'  THEN 0
                    WHEN attendance_records.status = 'excused' THEN 100
                    ELSE 0 END), 1) as attendance_rate")
            )
            ->groupBy(
                'students.id',
                'students.student_number',
                'students.fullname',
                'class_rooms.id',
                'class_rooms.name',
                'class_rooms.section'
            )
            ->havingRaw('ROUND(AVG(CASE
                WHEN attendance_records.status = \'present\' THEN 100
                WHEN attendance_records.status = \'late\'    THEN 80
                WHEN attendance_records.status = \'absent\'  THEN 0
                WHEN attendance_records.status = \'excused\' THEN 100
                ELSE 0 END), 1) < ?', [$threshold])
            ->orderBy('attendance_rate', 'asc')
            ->orderBy('students.fullname', 'asc');

        if ($selectedClassId) {
            $query->where('class_rooms.id', $selectedClassId);
        }

        $failingStudents = $query->get();

        $summary = [
            'total' => $failingStudents->count(),
            'critical' => $failingStudents->where('attendance_rate', '<', 50)->count(),
            'classes' => $failingStudents->pluck('class_room_id')->unique()->count(),
        ];

        return view('reports.index', compact('classRooms', 'failingStudents', 'summary', 'selectedClassId', 'threshold'));
    }
}
