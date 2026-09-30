<?php

namespace App\Services;

use App\Models\ClassRoom;
use App\Models\Term;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TermGradeCalculator
{
    private const KEYS = ['prelim', 'midterm', 'finals'];

    /**
     * @param  Collection<int, Term>  $terms
     * @return Collection<int, array{student_id: int, student_number: string, fullname: string, prelim: ?float, midterm: ?float, finals: ?float, overall: ?float}>
     */
    public function forClass(ClassRoom $classRoom, Collection $terms): Collection
    {
        $termsByKey = $terms->keyBy('key');

        $scoresByTermKey = [];
        foreach (self::KEYS as $key) {
            $scoresByTermKey[$key] = $this->scoresForTerm($classRoom, $termsByKey->get($key));
        }

        return $classRoom->students()
            ->orderBy('fullname')
            ->get(['students.id', 'students.student_number', 'students.fullname'])
            ->map(function ($student) use ($scoresByTermKey) {
                $termScores = [];
                foreach (self::KEYS as $key) {
                    $termScores[$key] = $scoresByTermKey[$key][$student->id] ?? null;
                }

                $present = array_filter($termScores, fn (?float $score) => $score !== null);

                return [
                    'student_id' => $student->id,
                    'student_number' => $student->student_number,
                    'fullname' => $student->fullname,
                    'prelim' => $termScores['prelim'],
                    'midterm' => $termScores['midterm'],
                    'finals' => $termScores['finals'],
                    'overall' => $present === [] ? null : round(array_sum($present) / count($present), 1),
                ];
            });
    }

    /**
     * @return array<int, float> student_id => average score, for students with at least one session in range
     */
    private function scoresForTerm(ClassRoom $classRoom, ?Term $term): array
    {
        if (! $term || ! $term->start_date || ! $term->end_date) {
            return [];
        }

        return DB::table('attendance_records')
            ->join('attendance_sessions', 'attendance_sessions.id', '=', 'attendance_records.attendance_session_id')
            ->where('attendance_sessions.class_room_id', $classRoom->id)
            ->whereBetween('attendance_sessions.session_date', [
                $term->start_date->toDateString(),
                $term->end_date->toDateString(),
            ])
            ->groupBy('attendance_records.student_id')
            ->selectRaw("attendance_records.student_id, AVG(CASE
                WHEN attendance_records.status = 'present'  THEN 100
                WHEN attendance_records.status = 'late'     THEN 80
                WHEN attendance_records.status = 'absent'   THEN 0
                WHEN attendance_records.status = 'excused'  THEN 100
                ELSE 0
            END) as average_score")
            ->pluck('average_score', 'attendance_records.student_id')
            ->map(fn ($score) => round((float) $score, 1))
            ->all();
    }
}
