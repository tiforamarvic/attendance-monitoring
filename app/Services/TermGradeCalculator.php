<?php

namespace App\Services;

use App\Models\AttendanceSession;
use App\Models\ClassRoom;
use App\Models\Term;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TermGradeCalculator
{
    private const KEYS = ['prelim', 'midterm', 'finals'];

    private const SCORE_SQL = "CASE
        WHEN attendance_records.status = 'present'  THEN 100
        WHEN attendance_records.status = 'excused'  THEN 90
        WHEN attendance_records.status = 'late'     THEN 85
        WHEN attendance_records.status = 'absent'   THEN 0
        ELSE 0
    END";

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
     * Per-date breakdown of one term: every session date the class held within
     * the term, and each student's score on each date (null when the student
     * has no record for that session).
     *
     * @return array{dates: array<int, string>, students: Collection<int, array{student_id: int, student_number: string, fullname: string, scores: array<string, ?float>, average: ?float}>}
     */
    public function dailyForTerm(ClassRoom $classRoom, ?Term $term): array
    {
        $dates = [];
        $scoresByStudent = [];

        if ($term && $term->start_date && $term->end_date) {
            $dates = AttendanceSession::query()
                ->where('class_room_id', $classRoom->id)
                ->whereDate('session_date', '>=', $term->start_date->toDateString())
                ->whereDate('session_date', '<=', $term->end_date->toDateString())
                ->orderBy('session_date')
                ->pluck('session_date')
                ->map(fn (Carbon $date) => $date->toDateString())
                ->all();

            $this->recordsInTerm($classRoom, $term)
                ->selectRaw('attendance_records.student_id, attendance_sessions.session_date, '.self::SCORE_SQL.' as score')
                ->get()
                ->each(function (object $record) use (&$scoresByStudent) {
                    $date = Carbon::parse($record->session_date)->toDateString();
                    $scoresByStudent[$record->student_id][$date] = (float) $record->score;
                });
        }

        $students = $classRoom->students()
            ->orderBy('fullname')
            ->get(['students.id', 'students.student_number', 'students.fullname'])
            ->map(function ($student) use ($dates, $scoresByStudent) {
                $scores = [];
                foreach ($dates as $date) {
                    $scores[$date] = $scoresByStudent[$student->id][$date] ?? null;
                }

                $recorded = array_filter($scores, fn (?float $score) => $score !== null);

                return [
                    'student_id' => $student->id,
                    'student_number' => $student->student_number,
                    'fullname' => $student->fullname,
                    'scores' => $scores,
                    'average' => $recorded === [] ? null : round(array_sum($recorded) / count($recorded), 1),
                ];
            });

        return ['dates' => $dates, 'students' => $students];
    }

    /**
     * @return array<int, float> student_id => average score, for students with at least one session in range
     */
    private function scoresForTerm(ClassRoom $classRoom, ?Term $term): array
    {
        if (! $term || ! $term->start_date || ! $term->end_date) {
            return [];
        }

        return $this->recordsInTerm($classRoom, $term)
            ->groupBy('attendance_records.student_id')
            ->selectRaw('attendance_records.student_id, AVG('.self::SCORE_SQL.') as average_score')
            ->pluck('average_score', 'attendance_records.student_id')
            ->map(fn ($score) => round((float) $score, 1))
            ->all();
    }

    /**
     * Attendance records for the class whose session falls within the term, inclusive.
     * Uses whereDate() because date-cast columns are stored with a time part on SQLite,
     * so a plain string comparison against the end date would drop the term's last day.
     */
    private function recordsInTerm(ClassRoom $classRoom, Term $term): Builder
    {
        return DB::table('attendance_records')
            ->join('attendance_sessions', 'attendance_sessions.id', '=', 'attendance_records.attendance_session_id')
            ->where('attendance_sessions.class_room_id', $classRoom->id)
            ->whereDate('attendance_sessions.session_date', '>=', $term->start_date->toDateString())
            ->whereDate('attendance_sessions.session_date', '<=', $term->end_date->toDateString());
    }
}
