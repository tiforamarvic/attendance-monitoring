<?php

namespace App\Http\Controllers;

use App\Exports\ClassGradesExport;
use App\Models\ClassRoom;
use App\Models\Term;
use App\Services\TermGradeCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GradesController extends Controller
{
    public function __construct(private readonly TermGradeCalculator $calculator) {}

    public function index(Request $request): View
    {
        $classRooms = ClassRoom::orderBy('name')->get(['id', 'name', 'section']);
        $terms = Term::whereIn('key', ['prelim', 'midterm', 'finals'])->get();
        $termsConfigured = $terms->every(fn (Term $term) => $term->start_date && $term->end_date);

        $selectedClassId = $request->input('class_id');
        $studentGrades = new Collection;

        if ($selectedClassId && $termsConfigured) {
            $classRoom = ClassRoom::findOrFail($selectedClassId);
            $studentGrades = $this->calculator->forClass($classRoom, $terms);
        }

        return view('grades.index', compact('classRooms', 'terms', 'termsConfigured', 'selectedClassId', 'studentGrades'));
    }

    public function export(Request $request): RedirectResponse|BinaryFileResponse
    {
        $classRoom = ClassRoom::findOrFail($request->input('class_id'));

        $terms = Term::whereIn('key', ['prelim', 'midterm', 'finals'])->get();
        $termsConfigured = $terms->every(fn (Term $term) => $term->start_date && $term->end_date);

        if (! $termsConfigured) {
            return redirect()->route('grades.index', ['class_id' => $classRoom->id])
                ->with('error', 'Configure term dates before exporting.');
        }

        return Excel::download(new ClassGradesExport($classRoom), "{$classRoom->name}-grades.xlsx");
    }
}
