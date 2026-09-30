<?php

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;

test('grades page prompts to configure terms when any term date is missing', function () {
    $response = $this->actingAs(User::factory()->create())->get(route('grades.index'));

    $response->assertOk();
    $response->assertViewHas('termsConfigured', false);
    // The view's copy isn't rendered through Blade's escaping {{ }}, so the
    // apostrophe stays literal in the response — assert a substring without
    // one rather than guessing at entity-encoding.
    $response->assertSee('configured yet');
});

test('grades page prompts to select a class once terms are configured', function () {
    Term::where('key', 'prelim')->update(['start_date' => '2026-01-01', 'end_date' => '2026-01-31']);
    Term::where('key', 'midterm')->update(['start_date' => '2026-02-01', 'end_date' => '2026-02-28']);
    Term::where('key', 'finals')->update(['start_date' => '2026-03-01', 'end_date' => '2026-03-31']);

    $response = $this->actingAs(User::factory()->create())->get(route('grades.index'));

    $response->assertOk();
    $response->assertSee('Select a class to view grades.');
});

test('grades page shows the student grade table once terms are configured and a class is selected', function () {
    Term::where('key', 'prelim')->update(['start_date' => '2026-01-01', 'end_date' => '2026-01-31']);
    Term::where('key', 'midterm')->update(['start_date' => '2026-02-01', 'end_date' => '2026-02-28']);
    Term::where('key', 'finals')->update(['start_date' => '2026-03-01', 'end_date' => '2026-03-31']);

    $classRoom = ClassRoom::factory()->create();
    $student = Student::factory()->create(['fullname' => 'Grade Student']);
    $classRoom->students()->attach($student->id);

    $session = AttendanceSession::factory()->create(['class_room_id' => $classRoom->id, 'session_date' => '2026-01-15']);
    AttendanceRecord::factory()->create(['attendance_session_id' => $session->id, 'student_id' => $student->id, 'status' => 'present']);

    $response = $this->actingAs(User::factory()->create())
        ->get(route('grades.index', ['class_id' => $classRoom->id]));

    $response->assertOk();
    $response->assertSee('Grade Student');
    $response->assertViewHas('studentGrades', function ($rows) use ($student) {
        $row = $rows->firstWhere('student_id', $student->id);

        return $row['prelim'] === 100.0 && $row['midterm'] === null && $row['overall'] === 100.0;
    });
});

test('a term score of exactly zero renders as 0 percent, not as no data', function () {
    Term::where('key', 'prelim')->update(['start_date' => '2026-01-01', 'end_date' => '2026-01-31']);
    Term::where('key', 'midterm')->update(['start_date' => '2026-02-01', 'end_date' => '2026-02-28']);
    Term::where('key', 'finals')->update(['start_date' => '2026-03-01', 'end_date' => '2026-03-31']);

    $classRoom = ClassRoom::factory()->create();
    $student = Student::factory()->create();
    $classRoom->students()->attach($student->id);

    $session = AttendanceSession::factory()->create(['class_room_id' => $classRoom->id, 'session_date' => '2026-01-15']);
    AttendanceRecord::factory()->create(['attendance_session_id' => $session->id, 'student_id' => $student->id, 'status' => 'absent']);

    $response = $this->actingAs(User::factory()->create())
        ->get(route('grades.index', ['class_id' => $classRoom->id]));

    $response->assertOk();
    $response->assertSeeInOrder(['0%', '—']);
});

test('a class with no enrolled students renders an empty table without error', function () {
    Term::where('key', 'prelim')->update(['start_date' => '2026-01-01', 'end_date' => '2026-01-31']);
    Term::where('key', 'midterm')->update(['start_date' => '2026-02-01', 'end_date' => '2026-02-28']);
    Term::where('key', 'finals')->update(['start_date' => '2026-03-01', 'end_date' => '2026-03-31']);

    $classRoom = ClassRoom::factory()->create();

    $response = $this->actingAs(User::factory()->create())
        ->get(route('grades.index', ['class_id' => $classRoom->id]));

    $response->assertOk();
    $response->assertViewHas('studentGrades', fn ($rows) => $rows->isEmpty());
});

test('an invalid class_id returns a 404 instead of a server error', function () {
    Term::where('key', 'prelim')->update(['start_date' => '2026-01-01', 'end_date' => '2026-01-31']);
    Term::where('key', 'midterm')->update(['start_date' => '2026-02-01', 'end_date' => '2026-02-28']);
    Term::where('key', 'finals')->update(['start_date' => '2026-03-01', 'end_date' => '2026-03-31']);

    $response = $this->actingAs(User::factory()->create())
        ->get(route('grades.index', ['class_id' => 999999]));

    $response->assertNotFound();
});
