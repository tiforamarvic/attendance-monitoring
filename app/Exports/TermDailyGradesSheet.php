<?php

namespace App\Exports;

use App\Models\ClassRoom;
use App\Models\Term;
use App\Services\TermGradeCalculator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * One sheet per term: a column per session date in the term's range, showing
 * the score each student got that day, followed by the term average.
 */
class TermDailyGradesSheet implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    /** @var array{dates: array<int, string>, students: Collection<int, array{student_number: string, fullname: string, scores: array<string, ?float>, average: ?float}>}|null */
    private ?array $breakdown = null;

    public function __construct(
        private readonly ClassRoom $classRoom,
        private readonly Term $term,
    ) {}

    public function title(): string
    {
        return $this->term->label;
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        $dateHeadings = array_map(
            fn (string $date) => Carbon::parse($date)->format('M j, Y'),
            $this->breakdown()['dates'],
        );

        return ['Student No.', 'Full Name', ...$dateHeadings, 'Term Average'];
    }

    /** @return array<int, array<int, mixed>> */
    public function array(): array
    {
        return $this->breakdown()['students']
            ->map(fn (array $row) => [
                $row['student_number'],
                $row['fullname'],
                ...array_values($row['scores']),
                $row['average'],
            ])
            ->all();
    }

    /**
     * @return array{dates: array<int, string>, students: Collection<int, array{student_number: string, fullname: string, scores: array<string, ?float>, average: ?float}>}
     */
    private function breakdown(): array
    {
        return $this->breakdown ??= (new TermGradeCalculator)->dailyForTerm($this->classRoom, $this->term);
    }
}
