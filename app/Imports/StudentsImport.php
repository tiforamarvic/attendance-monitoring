<?php

namespace App\Imports;

use App\Models\ClassRoom;
use App\Models\Student;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithSkipDuplicates;

class StudentsImport implements ToModel, WithHeadingRow, WithSkipDuplicates
{
    public function __construct(private readonly ClassRoom $classRoom) {}

    public function model(array $row): ?Student
    {
        $studentNumber = trim($row['student_number'] ?? $row['student_id'] ?? '');
        $fullname = trim($row['fullname'] ?? $row['full_name'] ?? $row['name'] ?? '');

        if (empty($studentNumber) || empty($fullname)) {
            return null;
        }

        $email = trim($row['email'] ?? $row['student_email'] ?? '') ?: null;

        $student = Student::where('student_number', $studentNumber)->first();

        if (! $student) {
            // Skip email if already taken by another student to avoid constraint violations
            if ($email && Student::where('email', $email)->exists()) {
                $email = null;
            }

            $student = Student::create([
                'student_number' => $studentNumber,
                'fullname' => $fullname,
                'email' => $email,
            ]);
        }

        $this->classRoom->students()->syncWithoutDetaching([$student->id]);

        return null;
    }
}
