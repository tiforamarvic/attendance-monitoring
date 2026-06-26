<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportStudentsRequest;
use App\Http\Requests\StoreStudentRequest;
use App\Imports\StudentsImport;
use App\Models\ClassRoom;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class StudentController extends Controller
{
    public function create(ClassRoom $classRoom): View
    {
        $existingStudents = Student::whereNotIn('id', $classRoom->students()->pluck('students.id'))
            ->orderBy('fullname')
            ->get(['id', 'student_number', 'fullname', 'email']);

        return view('classes.students.create', compact('classRoom', 'existingStudents'));
    }

    public function store(StoreStudentRequest $request, ClassRoom $classRoom): RedirectResponse
    {
        $student = Student::where('student_number', $request->validated('student_number'))->first();

        if (! $student) {
            $email = $request->validated('email');

            if ($email) {
                $emailOwner = Student::where('email', $email)->first();
                if ($emailOwner) {
                    return back()->withInput()
                        ->withErrors(['email' => "This email is already registered to {$emailOwner->fullname} (#{$emailOwner->student_number})."]);
                }
            }

            $student = Student::create([
                'student_number' => $request->validated('student_number'),
                'fullname' => $request->validated('fullname'),
                'email' => $email ?: null,
            ]);
        }

        if ($classRoom->students()->where('student_id', $student->id)->exists()) {
            return redirect()->route('classes.show', $classRoom)
                ->with('warning', "{$student->fullname} is already enrolled in this class.");
        }

        $classRoom->students()->attach($student->id);

        return redirect()->route('classes.show', $classRoom)
            ->with('success', "{$student->fullname} added successfully.");
    }

    public function destroy(ClassRoom $classRoom, Student $student): RedirectResponse
    {
        $classRoom->students()->detach($student->id);

        return redirect()->route('classes.show', $classRoom)
            ->with('success', "{$student->fullname} removed from this class.");
    }

    public function deleteStudent(ClassRoom $classRoom, Student $student): RedirectResponse
    {
        $name = $student->fullname;
        $student->delete();

        return redirect()->route('classes.show', $classRoom)
            ->with('success', "{$name} has been permanently deleted from all classes.");
    }

    public function importForm(ClassRoom $classRoom): View
    {
        return view('classes.students.import', compact('classRoom'));
    }

    public function import(ImportStudentsRequest $request, ClassRoom $classRoom): RedirectResponse
    {
        Excel::import(new StudentsImport($classRoom), $request->file('file'));

        return redirect()->route('classes.show', $classRoom)
            ->with('success', 'Students imported successfully.');
    }
}
