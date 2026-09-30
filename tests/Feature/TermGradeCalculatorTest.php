<?php

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\Term;
use App\Services\TermGradeCalculator;
use Illuminate\Support\Collection;

beforeEach(function () {
    Term::where('key', 'prelim')->update(['start_date' => '2026-01-01', 'end_date' => '2026-01-31']);
    Term::where('key', 'midterm')->update(['start_date' => '2026-02-01', 'end_date' => '2026-02-28']);
    Term::where('key', 'finals')->update(['start_date' => '2026-03-01', 'end_date' => '2026-03-31']);
});

function configuredTerms(): Collection
{
    return Term::whereIn('key', ['prelim', 'midterm', 'finals'])->get();
}

test('a student with sessions only in the prelim range gets a prelim score and null for the other terms', function () {
    $classRoom = ClassRoom::factory()->create();
    $student = Student::factory()->create();
    $classRoom->students()->attach($student->id);

    $session = AttendanceSession::factory()->create([
        'class_room_id' => $classRoom->id,
        'session_date' => '2026-01-15',
    ]);
    AttendanceRecord::factory()->create([
        'attendance_session_id' => $session->id,
        'student_id' => $student->id,
        'status' => 'present',
    ]);

    $row = (new TermGradeCalculator)->forClass($classRoom, configuredTerms())->firstWhere('student_id', $student->id);

    expect($row['prelim'])->toBe(100.0);
    expect($row['midterm'])->toBeNull();
    expect($row['finals'])->toBeNull();
    expect($row['overall'])->toBe(100.0);
});

test('a student with sessions in all three term ranges gets all three scores and a correct overall average', function () {
    $classRoom = ClassRoom::factory()->create();
    $student = Student::factory()->create();
    $classRoom->students()->attach($student->id);

    $prelimSession = AttendanceSession::factory()->create(['class_room_id' => $classRoom->id, 'session_date' => '2026-01-10']);
    $midtermSession = AttendanceSession::factory()->create(['class_room_id' => $classRoom->id, 'session_date' => '2026-02-10']);
    $finalsSession = AttendanceSession::factory()->create(['class_room_id' => $classRoom->id, 'session_date' => '2026-03-10']);

    AttendanceRecord::factory()->create(['attendance_session_id' => $prelimSession->id, 'student_id' => $student->id, 'status' => 'present']);
    AttendanceRecord::factory()->create(['attendance_session_id' => $midtermSession->id, 'student_id' => $student->id, 'status' => 'late']);
    AttendanceRecord::factory()->create(['attendance_session_id' => $finalsSession->id, 'student_id' => $student->id, 'status' => 'absent']);

    $row = (new TermGradeCalculator)->forClass($classRoom, configuredTerms())->firstWhere('student_id', $student->id);

    expect($row['prelim'])->toBe(100.0);
    expect($row['midterm'])->toBe(85.0);
    expect($row['finals'])->toBe(0.0);
    expect($row['overall'])->toBe(61.7);
});

test('a session outside all term ranges does not affect any term score or the overall', function () {
    $classRoom = ClassRoom::factory()->create();
    $student = Student::factory()->create();
    $classRoom->students()->attach($student->id);

    $outOfRangeSession = AttendanceSession::factory()->create([
        'class_room_id' => $classRoom->id,
        'session_date' => '2026-06-01',
    ]);
    AttendanceRecord::factory()->create([
        'attendance_session_id' => $outOfRangeSession->id,
        'student_id' => $student->id,
        'status' => 'absent',
    ]);

    $row = (new TermGradeCalculator)->forClass($classRoom, configuredTerms())->firstWhere('student_id', $student->id);

    expect($row['prelim'])->toBeNull();
    expect($row['midterm'])->toBeNull();
    expect($row['finals'])->toBeNull();
    expect($row['overall'])->toBeNull();
});

test('a real score of exactly 0 percent is distinct from no data', function () {
    $classRoom = ClassRoom::factory()->create();
    $student = Student::factory()->create();
    $classRoom->students()->attach($student->id);

    $session = AttendanceSession::factory()->create(['class_room_id' => $classRoom->id, 'session_date' => '2026-01-15']);
    AttendanceRecord::factory()->create(['attendance_session_id' => $session->id, 'student_id' => $student->id, 'status' => 'absent']);

    $row = (new TermGradeCalculator)->forClass($classRoom, configuredTerms())->firstWhere('student_id', $student->id);

    expect($row['prelim'])->toBe(0.0);
    expect($row['prelim'])->not->toBeNull();
    expect($row['midterm'])->toBeNull();
    expect($row['overall'])->toBe(0.0);
});

test('a term score never includes attendance from a different class the student is also enrolled in', function () {
    $classA = ClassRoom::factory()->create();
    $classB = ClassRoom::factory()->create();
    $student = Student::factory()->create();
    $classA->students()->attach($student->id);
    $classB->students()->attach($student->id);

    $sessionA = AttendanceSession::factory()->create(['class_room_id' => $classA->id, 'session_date' => '2026-01-10']);
    AttendanceRecord::factory()->create(['attendance_session_id' => $sessionA->id, 'student_id' => $student->id, 'status' => 'present']);

    $sessionB = AttendanceSession::factory()->create(['class_room_id' => $classB->id, 'session_date' => '2026-01-12']);
    AttendanceRecord::factory()->create(['attendance_session_id' => $sessionB->id, 'student_id' => $student->id, 'status' => 'absent']);

    $rowForClassA = (new TermGradeCalculator)->forClass($classA, configuredTerms())->firstWhere('student_id', $student->id);

    // Class A's only session was a 'present' (100). If class B's 'absent' record leaked in,
    // the average would drop to 50.
    expect($rowForClassA['prelim'])->toBe(100.0);
});

test('a class with no enrolled students returns an empty collection without error', function () {
    $classRoom = ClassRoom::factory()->create();

    $rows = (new TermGradeCalculator)->forClass($classRoom, configuredTerms());

    expect($rows)->toHaveCount(0);
});
