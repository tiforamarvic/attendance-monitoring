<?php

namespace App\Exports;

use App\Models\ClassRoom;
use App\Models\Term;
use App\Services\TermGradeCalculator;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class ClassGradesSummarySheet implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    /**
     * @param  Collection<int, Term>  $terms
     */
    public function __construct(
        private readonly ClassRoom $classRoom,
        private readonly Collection $terms,
    ) {}

    public function collection(): Collection
    {
        return (new TermGradeCalculator)->forClass($this->classRoom, $this->terms);
    }

    public function title(): string
    {
        return 'Summary';
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
