<?php

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;

test('exporting grades downloads an xlsx file when terms are configured', function () {
    Term::where('key', 'prelim')->update(['start_date' => '2026-01-01', 'end_date' => '2026-01-31']);
    Term::where('key', 'midterm')->update(['start_date' => '2026-02-01', 'end_date' => '2026-02-28']);
    Term::where('key', 'finals')->update(['start_date' => '2026-03-01', 'end_date' => '2026-03-31']);

    $classRoom = ClassRoom::factory()->create(['name' => 'Export Class']);
    $student = Student::factory()->create();
    $classRoom->students()->attach($student->id);

    $session = AttendanceSession::factory()->create(['class_room_id' => $classRoom->id, 'session_date' => '2026-01-15']);
    AttendanceRecord::factory()->create(['attendance_session_id' => $session->id, 'student_id' => $student->id, 'status' => 'present']);

    Excel::fake();

    $this->actingAs(User::factory()->create())
        ->get(route('grades.export', ['class_id' => $classRoom->id]))
        ->assertOk();

    Excel::assertDownloaded('Export Class-grades.xlsx');
});

test('exporting grades redirects back with an error when terms are not configured', function () {
    $classRoom = ClassRoom::factory()->create();

    $response = $this->actingAs(User::factory()->create())
        ->get(route('grades.export', ['class_id' => $classRoom->id]));

    $response->assertRedirect(route('grades.index', ['class_id' => $classRoom->id]));
    $response->assertSessionHas('error');
});

test('an invalid class_id returns a 404 instead of a server error', function () {
    Term::where('key', 'prelim')->update(['start_date' => '2026-01-01', 'end_date' => '2026-01-31']);
    Term::where('key', 'midterm')->update(['start_date' => '2026-02-01', 'end_date' => '2026-02-28']);
    Term::where('key', 'finals')->update(['start_date' => '2026-03-01', 'end_date' => '2026-03-31']);

    $response = $this->actingAs(User::factory()->create())
        ->get(route('grades.export', ['class_id' => 999999]));

    $response->assertNotFound();
});
