<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttendanceRequest;
use App\Models\AttendanceSession;
use App\Models\ClassRoom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(): View
    {
        $sessions = AttendanceSession::with('classRoom')
            ->withCount([
                'attendanceRecords',
                'attendanceRecords as present_count' => fn ($q) => $q->where('status', 'present'),
                'attendanceRecords as late_count' => fn ($q) => $q->where('status', 'late'),
                'attendanceRecords as absent_count' => fn ($q) => $q->where('status', 'absent'),
                'attendanceRecords as excused_count' => fn ($q) => $q->where('status', 'excused'),
            ])
            ->orderByDesc('session_date')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('attendance.index', compact('sessions'));
    }

    public function create(Request $request): View
    {
        $classRooms = ClassRoom::orderBy('name')->get();
        $selectedClass = null;
        $students = collect();
        $existingSession = null;
        $date = $request->input('date', today()->toDateString());

        if ($request->filled('class_id')) {
            $selectedClass = ClassRoom::findOrFail($request->class_id);
            $students = $selectedClass->students()->orderBy('fullname')->get();

            $existingSession = AttendanceSession::where('class_room_id', $selectedClass->id)
                ->whereDate('session_date', $date)
                ->first();
        }

        return view('attendance.create', compact('classRooms', 'selectedClass', 'students', 'existingSession', 'date'));
    }

    public function store(StoreAttendanceRequest $request): RedirectResponse
    {
        $existing = AttendanceSession::where('class_room_id', $request->validated('class_room_id'))
            ->whereDate('session_date', $request->validated('session_date'))
            ->first();

        if ($existing) {
            return redirect()->route('attendance.show', $existing)
                ->with('warning', 'A session for this class on this date already exists.');
        }

        $session = AttendanceSession::create([
            'class_room_id' => $request->validated('class_room_id'),
            'session_date' => $request->validated('session_date'),
            'notes' => $request->validated('notes'),
        ]);

        foreach ($request->validated('attendance') as $studentId => $data) {
            $session->attendanceRecords()->create([
                'student_id' => $studentId,
                'status' => $data['status'],
                'remarks' => $data['remarks'] ?? null,
            ]);
        }

        return redirect()->route('attendance.show', $session)
            ->with('success', 'Attendance saved successfully.');
    }

    public function show(AttendanceSession $attendanceSession): View
    {
        $attendanceSession->load(['classRoom', 'attendanceRecords.student']);
        $recordsByStudent = $attendanceSession->attendanceRecords->keyBy('student_id');

        return view('attendance.show', compact('attendanceSession', 'recordsByStudent'));
    }

    public function update(Request $request, AttendanceSession $attendanceSession): RedirectResponse
    {
        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
            'attendance' => ['required', 'array'],
            'attendance.*.status' => ['required', 'in:present,late,absent,excused'],
            'attendance.*.remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $attendanceSession->update(['notes' => $data['notes'] ?? null]);

        foreach ($data['attendance'] as $studentId => $row) {
            $attendanceSession->attendanceRecords()
                ->where('student_id', $studentId)
                ->update([
                    'status' => $row['status'],
                    'remarks' => $row['remarks'] ?? null,
                ]);
        }

        return redirect()->route('attendance.show', $attendanceSession)
            ->with('success', 'Attendance updated successfully.');
    }

    public function destroy(AttendanceSession $attendanceSession): RedirectResponse
    {
        $attendanceSession->delete();

        return redirect()->route('attendance.index')
            ->with('success', 'Attendance session deleted.');
    }
}
