<?php

namespace App\Exports;

use App\Models\ClassRoom;
use App\Models\Term;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ClassGradesExport implements WithMultipleSheets
{
    private const TERM_KEYS = ['prelim', 'midterm', 'finals'];

    public function __construct(private readonly ClassRoom $classRoom) {}

    /** @return array<int, object> */
    public function sheets(): array
    {
        $terms = Term::whereIn('key', self::TERM_KEYS)->get()
            ->sortBy(fn (Term $term) => array_search($term->key, self::TERM_KEYS))
            ->values();

        $sheets = [new ClassGradesSummarySheet($this->classRoom, $terms)];

        foreach ($terms as $term) {
            $sheets[] = new TermDailyGradesSheet($this->classRoom, $term);
        }

        return $sheets;
    }
}
