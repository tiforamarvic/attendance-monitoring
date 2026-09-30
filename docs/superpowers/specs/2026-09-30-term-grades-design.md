# Term Grades Module — Design

## Problem

The teacher currently sees attendance-based grades only as a single running average across a class's entire history (Dashboard's poor-attendance table, Reports page). There's no way to see how a student did specifically in **Prelim**, **Midterm**, or **Finals** — the standard academic grading periods — nor to export that breakdown. This feature adds term-scoped grading and an Excel export.

## Scope

- One global academic calendar: a single set of Prelim/Midterm/Finals date ranges applies to every class (not configurable per class).
- Reuses the existing grade formula (Present=100, Late=80, Absent=0, Excused=100; grade = average of session scores), scoped to each term's date range instead of the whole class history.
- Per-class Grades table (Student × Prelim/Midterm/Finals/Overall) and a per-class Excel export.
- Out of scope: per-class term overrides, weighted overall grades (simple average per user decision), multi-class/whole-school export, historical/versioned terms across school years (the 3 term rows are simply edited in place each new term).

## Data Model

### New migration: `terms` table

```php
Schema::create('terms', function (Blueprint $table) {
    $table->id();
    $table->string('key')->unique();   // 'prelim' | 'midterm' | 'finals'
    $table->string('label');           // 'Prelim' | 'Midterm' | 'Finals'
    $table->date('start_date')->nullable();
    $table->date('end_date')->nullable();
    $table->timestamps();
});
```

The same migration seeds the 3 fixed rows (`prelim`/Prelim, `midterm`/Midterm, `finals`/Finals) with null dates via `DB::table('terms')->insert(...)` in `up()`. There is no create/delete UI or route for terms — only editing the two date columns on the existing 3 rows. This keeps the feature to "configure 3 known periods" rather than a general CRUD subsystem, matching the global-calendar decision.

### New model: `App\Models\Term`

```php
class Term extends Model
{
    protected $fillable = ['start_date', 'end_date'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date'];
    }
}
```

No relationships — `Term` rows are looked up by `key` when scoping grade queries; they don't join into the attendance schema.

## Term Settings

### Route

```php
Route::get('/terms', [TermController::class, 'edit'])->name('terms.edit');
Route::put('/terms', [TermController::class, 'update'])->name('terms.update');
```

Both behind `auth` middleware, alongside the existing route group in `routes/web.php`.

### `TermController`

- `edit()`: fetches the 3 `Term` rows individually by key (`Term::where('key', 'prelim')->first()`, etc.) so display order is deterministic without relying on insertion order or a raw SQL sort, returns `terms.edit` view.
- `update(UpdateTermsRequest $request)`: request shape is `terms: [key => ['start_date' => ..., 'end_date' => ...]]` for all 3 keys. Updates each `Term` row by `key`. Redirects back to `/terms` with a success flash message (staying on the settings page so the teacher sees the saved values confirmed).

### `UpdateTermsRequest` validation

For each of the 3 known keys (`prelim`, `midterm`, `finals`):
- `terms.{key}.start_date` — `nullable|date`
- `terms.{key}.end_date` — `nullable|date|after_or_equal:terms.{key}.start_date`
- Both must be present together: if `start_date` is set, `end_date` is `required`, and vice versa (`required_with` both directions). This prevents a half-configured term silently matching an open-ended range.

No cross-term ordering validation (e.g. Prelim not overlapping Midterm) — the teacher is trusted to enter sane ranges; keeps validation simple per the approved design.

### View: `resources/views/terms/edit.blade.php`

A single form, 3 rows (Prelim / Midterm / Finals), each with a start-date and end-date input, styled consistently with the existing input conventions (`bg-slate-50 border border-slate-300 rounded-lg`). One submit button. Uses the standard `layouts.app` extends pattern.

## Grades Page

### Route

```php
Route::get('/grades', [GradesController::class, 'index'])->name('grades.index');
Route::get('/grades/export', [GradesController::class, 'export'])->name('grades.export');
```

### `GradesController@index`

- Query params: `class_id` (optional — like Reports, no class selected means show the picker with no table yet).
- Loads all 3 `Term` rows.
- If any term is missing both dates (unconfigured), the view shows a prompt: "Configure your term dates first" linking to `/terms`, instead of attempting to compute grades.
- If a class is selected and all 3 terms have dates: for each enrolled student, compute 3 term scores + overall using the query approach below.
- Renders `grades.index` with `classRooms`, `selectedClassId`, `terms`, `studentGrades` (empty if no class selected or terms unconfigured).

### Grade computation

One query per term (3 total), each scoped to the term's date range, mirroring the existing Dashboard/Reports pattern — `students` joined through `class_room_student` → `attendance_sessions` (filtered `whereBetween('session_date', [term.start_date, term.end_date])`) → `attendance_records`, grouped by student, with `AVG(CASE WHEN status = 'present' THEN 100 ...)` as the score. Each query returns `student_id => average_score` for students who have at least one session in that range; students with none are simply absent from that term's result set, which the merge step below treats as "no data." This is what makes a term with no data yet show as "—" instead of a false 0%.

None of these queries need a `HAVING` clause at all (there's no threshold filter here, unlike Reports) — so the alias-in-`HAVING` bug that broke Dashboard/Reports on Postgres doesn't apply here. Worth stating explicitly since it's the same query shape as those two.

In PHP, merge the 3 term results per student (indexed by `student_id`) into rows of `{student, prelim, midterm, finals, overall}`, where each term value is `null` if that student had no matching query result, and `overall` is the average of the non-null term values (or `null` if all three are unset).

### View: `resources/views/grades/index.blade.php`

Same filter-bar pattern as `reports/index.blade.php` (class dropdown, no threshold this time), plus an "Export" button (visible once a class is selected) linking to `/grades/export?class_id=...`, and a small "Configure Terms" link to `/terms`. Table: Student | Prelim | Midterm | Finals | Overall, each term cell showing `{value}%` or `—`.

## Excel Export

### `App\Exports\ClassGradesExport`

New class using `maatwebsite/excel`'s `FromCollection` + `WithHeadings` (+ `WithMapping`), constructed with a `ClassRoom` (mirrors the existing `StudentsImport`'s constructor-injection pattern). Reuses the exact same grade-computation logic as `GradesController@index` — extracted into a small shared method/class (see Implementation Note) so the on-screen table and the export can never drift out of sync.

Columns: Student No., Full Name, Prelim, Midterm, Finals, Overall (blank cell, not "0", when a term has no data).

### `GradesController@export`

Validates `class_id` is present and belongs to an existing `ClassRoom` (404 otherwise). If terms aren't fully configured, redirects back to `/grades` with an error flash instead of exporting a meaningless file. Otherwise: `return Excel::download(new ClassGradesExport($classRoom), "{$classRoom->name}-grades.xlsx");`.

## Implementation Note: Shared Grade Calculation

To avoid duplicating the 3-query merge logic between `GradesController@index` and `ClassGradesExport`, it's extracted into a small dedicated class, e.g. `App\Services\TermGradeCalculator` with a single method `forClass(ClassRoom $classRoom, Collection $terms): Collection` returning the merged per-student rows described above. Both the controller and the export class call this. This is the one piece of "shared infrastructure" this feature needs — everything else follows the existing Controller → Blade view / Controller → Export class pattern already used elsewhere in the app.

## Navigation

Add `['route' => 'grades.index', 'label' => 'Grades']` to the nav array in `resources/views/layouts/app.blade.php`, positioned after `reports.index`. No separate nav entry for `/terms` — reached via the "Configure Terms" link on the Grades page.

## Testing (Pest feature tests)

- `TermGradeCalculator` (or the controller, exercised via feature test): a student with sessions only in Prelim's date range gets a Prelim score, and `null` for Midterm/Finals; overall equals the Prelim score alone.
- A student with sessions in all 3 ranges gets all 3 scores plus a correct overall average.
- A session whose date falls **outside** all 3 term ranges doesn't affect any term score (boundary case).
- `GradesController@index` shows the "configure terms first" prompt when any term is missing dates, and the real table once all 3 are configured.
- `TermController@update` validation: setting only `start_date` (no `end_date`) for a term fails validation; `end_date` before `start_date` fails validation.
- `ClassGradesExport` (or `GradesController@export`) produces a downloadable response with the expected headings/rows for a seeded class; exporting is blocked (redirect + flash) when terms aren't configured.

## Out of scope / explicit non-goals

- No per-class term overrides.
- No weighted overall grade (simple average only, per approved design).
- No multi-class / whole-school export in one file.
- No historical term periods — editing `/terms` always overwrites the current 3 rows in place; there's no year/semester entity anywhere else in the schema to attach history to.
- No cross-term date-range overlap validation.
