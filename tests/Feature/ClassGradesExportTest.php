<?php

use App\Exports\ClassGradesExport;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

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

test('an array class_id returns a 404 instead of a server error', function () {
    Term::where('key', 'prelim')->update(['start_date' => '2026-01-01', 'end_date' => '2026-01-31']);
    Term::where('key', 'midterm')->update(['start_date' => '2026-02-01', 'end_date' => '2026-02-28']);
    Term::where('key', 'finals')->update(['start_date' => '2026-03-01', 'end_date' => '2026-03-31']);

    // Wraps a real, existing id in an array: findOrFail() with an array whose
    // ids all resolve does NOT throw ModelNotFoundException — it returns a
    // Collection instead of a single model, which is what the guard must
    // reject before that Collection reaches code that expects a ClassRoom.
    $classRoom = ClassRoom::factory()->create();

    $response = $this->actingAs(User::factory()->create())
        ->get(route('grades.export', ['class_id' => [$classRoom->id]]));

    $response->assertNotFound();
});

test('exporting a class whose name contains a slash does not crash', function () {
    Term::where('key', 'prelim')->update(['start_date' => '2026-01-01', 'end_date' => '2026-01-31']);
    Term::where('key', 'midterm')->update(['start_date' => '2026-02-01', 'end_date' => '2026-02-28']);
    Term::where('key', 'finals')->update(['start_date' => '2026-03-01', 'end_date' => '2026-03-31']);

    $classRoom = ClassRoom::factory()->create(['name' => 'IT 101/A']);

    // Deliberately not using Excel::fake() here: the bug this test guards
    // against is in how the real download response builds its filename
    // header, which a faked Excel response never exercises.
    $response = $this->actingAs(User::factory()->create())
        ->get(route('grades.export', ['class_id' => $classRoom->id]));

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->not->toContain('/');
});

test('the export has a summary sheet plus a per-date sheet for each term', function () {
    Term::where('key', 'prelim')->update(['start_date' => '2026-08-29', 'end_date' => '2026-09-29']);
    Term::where('key', 'midterm')->update(['start_date' => '2026-09-30', 'end_date' => '2026-10-30']);
    Term::where('key', 'finals')->update(['start_date' => '2026-10-31', 'end_date' => '2026-11-30']);

    $classRoom = ClassRoom::factory()->create();
    $student = Student::factory()->create(['student_number' => '2026-0001', 'fullname' => 'Juan Dela Cruz']);
    $classRoom->students()->attach($student->id);

    foreach (['2026-08-29' => 'present', '2026-09-05' => 'late', '2026-09-29' => 'excused'] as $date => $status) {
        $session = AttendanceSession::factory()->create(['class_room_id' => $classRoom->id, 'session_date' => $date]);
        AttendanceRecord::factory()->create(['attendance_session_id' => $session->id, 'student_id' => $student->id, 'status' => $status]);
    }

    $path = tempnam(sys_get_temp_dir(), 'grades').'.xlsx';
    file_put_contents($path, Excel::raw(new ClassGradesExport($classRoom), ExcelFormat::XLSX));
    $workbook = IOFactory::load($path);
    unlink($path);

    expect($workbook->getSheetNames())->toBe(['Summary', 'Prelim', 'Midterm', 'Finals']);

    $prelim = $workbook->getSheetByName('Prelim')->toArray();
    expect($prelim[0])->toBe(['Student No.', 'Full Name', 'Aug 29, 2026', 'Sep 5, 2026', 'Sep 29, 2026', 'Term Average']);
    expect($prelim[1])->toEqual(['2026-0001', 'Juan Dela Cruz', 100, 85, 90, 91.7]);

    $summary = $workbook->getSheetByName('Summary')->toArray();
    expect($summary[1][2])->toEqual(91.7);

    // A term with no sessions yet still gets a sheet, with just the name and average columns.
    expect($workbook->getSheetByName('Midterm')->toArray()[0])->toBe(['Student No.', 'Full Name', 'Term Average']);
});
