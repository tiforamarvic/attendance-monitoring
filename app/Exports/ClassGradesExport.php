<?php

namespace App\Exports;

use App\Models\ClassRoom;
use App\Models\Term;
use App\Services\TermGradeCalculator;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ClassGradesExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private readonly ClassRoom $classRoom) {}

    public function collection(): Collection
    {
        $terms = Term::whereIn('key', ['prelim', 'midterm', 'finals'])->get();

        return (new TermGradeCalculator)->forClass($this->classRoom, $terms);
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return ['Student No.', 'Full Name', 'Prelim', 'Midterm', 'Finals', 'Overall'];
    }

    /**
     * @param  array{student_number: string, fullname: string, prelim: ?float, midterm: ?float, finals: ?float, overall: ?float}  $row
     * @return array<int, mixed>
     */
    public function map($row): array
    {
        return [
            $row['student_number'],
            $row['fullname'],
            $row['prelim'],
            $row['midterm'],
            $row['finals'],
            $row['overall'],
        ];
    }
}
