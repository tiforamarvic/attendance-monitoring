# Term Grades Module Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let the teacher see each student's attendance-based grade broken into Prelim/Midterm/Finals (plus an overall average) for a chosen class, configure the date range each term covers, and export that table to Excel.

**Architecture:** One new `terms` table holds exactly 3 fixed rows (prelim/midterm/finals) with editable date ranges, seeded by migration. A single `App\Services\TermGradeCalculator` computes per-student term scores from `attendance_records`/`attendance_sessions` scoped by each term's date range, reusing the existing Present=100/Late=80/Absent=0/Excused=100 formula. Both the new Grades page and its Excel export call this one calculator so they can never disagree.

**Tech Stack:** Laravel 13 / PHP 8.4, Eloquent, `maatwebsite/excel` (already a dependency), Pest v4, Tailwind (CDN, no build step — see existing layout files), PostgreSQL in production / SQLite in local dev and tests.

**Spec:** `docs/superpowers/specs/2026-09-30-term-grades-design.md`

## Global Constraints

- One global academic calendar: Prelim/Midterm/Finals date ranges apply to every class; no per-class overrides.
- Reuse the existing grade formula exactly: Present=100, Late=80, Absent=0, Excused=100, grade = average of scores.
- Overall grade = simple average of only the terms that have data (a term with zero sessions in range is `null`, not `0`).
- Excel export covers one class at a time (not a whole-school export).
- No cross-term date-range overlap validation — only `end_date >= start_date` per individual term.
- No create/delete UI for terms — only editing the 2 date columns on the 3 fixed, migration-seeded rows.
- After every PHP change: run `vendor/bin/pint --dirty --format agent`.
- Create files via `php artisan make:` commands with `--no-interaction` where applicable (migration, model, controller, request, export, test), then edit the generated file.
- Run tests with `php artisan test --compact`, optionally `--filter=Name`.
- Do not delete or modify the pre-existing unrelated `ExampleTest` failure (`GET /` returns 302, not 200) — out of scope for this feature.

## Review Focus

- An invalid/nonexistent `class_id` passed to `/grades` or `/grades/export` must degrade to a clean 404, not an unhandled 500.
- A class with zero enrolled students must render an empty grades table without error (not throw when the roster collection is empty).
- Clearing a previously-configured term's dates back to empty via the settings form must be accepted, not rejected as "missing end date."
- A student's term score must never leak in attendance data from a *different* class the student is also enrolled in — each term score is scoped to one class's sessions only.
- A real score of exactly 0% (all-absent in a term) must render as `0%`, not be confused with "no data yet" (`—`) — the null-vs-zero distinction must survive from the SQL layer through to the Blade/export output (a `!== null` check, never a falsy check).

---

### Task 1: `terms` table, seed data, and `Term` model

**Files:**
- Create: `database/migrations/2026_09_30_000001_create_terms_table.php`
- Create: `app/Models/Term.php`
- Create: `database/factories/TermFactory.php`
- Test: `tests/Feature/TermModelTest.php`

**Interfaces:**
- Produces: `App\Models\Term` — Eloquent model over table `terms` (columns: `id`, `key` unique string, `label` string, `start_date` nullable date, `end_date` nullable date, timestamps). `$fillable = ['start_date', 'end_date']`. `start_date`/`end_date` cast to `date`. After migrating, exactly 3 rows exist with `key` values `'prelim'`, `'midterm'`, `'finals'` and matching `label` values `'Prelim'`, `'Midterm'`, `'Finals'`, both dates `null`.

- [ ] **Step 1: Generate the migration**

Run: `php artisan make:migration create_terms_table --no-interaction`

- [ ] **Step 2: Write the migration**

Replace the generated file's contents with:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // 'prelim' | 'midterm' | 'finals'
            $table->string('label');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });

        DB::table('terms')->insert([
            ['key' => 'prelim', 'label' => 'Prelim', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'midterm', 'label' => 'Midterm', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'finals', 'label' => 'Finals', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('terms');
    }
};
```

Rename the file (if the generated timestamp prefix differs) so it is exactly `database/migrations/2026_09_30_000001_create_terms_table.php` — this keeps it ordered after all existing migrations.

- [ ] **Step 3: Run the migration**

Run: `php artisan migrate`
Expected: `Migrating: 2026_09_30_000001_create_terms_table` then `Migrated:` with no errors.

- [ ] **Step 4: Generate the model**

Run: `php artisan make:model Term --no-interaction`

- [ ] **Step 5: Write the model**

Replace `app/Models/Term.php` with:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Term extends Model
{
    /** @use HasFactory<\Database\Factories\TermFactory> */
    use HasFactory;

    protected $fillable = [
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }
}
```

- [ ] **Step 6: Write the factory**

Replace `database/factories/TermFactory.php` (generated alongside the model) with:

```php
<?php

namespace Database\Factories;

use App\Models\Term;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Term>
 */
class TermFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2),
            'label' => fake()->words(2, true),
            'start_date' => null,
            'end_date' => null,
        ];
    }
}
```

This factory exists for model-creation convention consistency; the actual 3 term rows used by the app come from the migration seed above, never from this factory.

- [ ] **Step 7: Write the failing test**

Create `tests/Feature/TermModelTest.php`:

```php
<?php

use App\Models\Term;

test('migrating seeds exactly the three fixed term rows with null dates', function () {
    $terms = Term::orderBy('key')->get();

    expect($terms->pluck('key')->all())->toBe(['finals', 'midterm', 'prelim']);
    expect($terms->pluck('label')->all())->toBe(['Finals', 'Midterm', 'Prelim']);
    $terms->each(function (Term $term) {
        expect($term->start_date)->toBeNull();
        expect($term->end_date)->toBeNull();
    });
});

test('start_date and end_date are cast to Carbon dates', function () {
    Term::where('key', 'prelim')->update(['start_date' => '2026-01-01', 'end_date' => '2026-01-31']);

    $prelim = Term::where('key', 'prelim')->first();

    expect($prelim->start_date)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
    expect($prelim->start_date->toDateString())->toBe('2026-01-01');
    expect($prelim->end_date->toDateString())->toBe('2026-01-31');
});
```

- [ ] **Step 8: Run the test**

Run: `php artisan test --compact --filter=TermModelTest`
Expected: PASS (the migration already seeds the rows, so this confirms the seed and casts are correct — no separate "make it pass" step needed here since Steps 1-6 already implement the behavior under test).

- [ ] **Step 9: Format and commit**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add database/migrations/2026_09_30_000001_create_terms_table.php app/Models/Term.php database/factories/TermFactory.php tests/Feature/TermModelTest.php
git commit -m "Add terms table, Term model, and seeded Prelim/Midterm/Finals rows"
```

---

### Task 2: `TermGradeCalculator` service

**Files:**
- Create: `app/Services/TermGradeCalculator.php`
- Test: `tests/Feature/TermGradeCalculatorTest.php`

**Interfaces:**
- Consumes: `App\Models\Term` (Task 1) — reads `key`, `start_date`, `end_date`. `App\Models\ClassRoom` — `$classRoom->students()` (existing `belongsToMany` relation) and `$classRoom->id`.
- Produces: `App\Services\TermGradeCalculator::forClass(ClassRoom $classRoom, Collection $terms): Collection`. `$terms` is any collection of `Term` models (order doesn't matter — matched internally by `key`). Returns a `Collection` of associative arrays, one per enrolled student, each shaped:
  ```php
  [
      'student_id' => int,
      'student_number' => string,
      'fullname' => string,
      'prelim' => ?float,   // rounded to 1 decimal, or null if no sessions in range
      'midterm' => ?float,
      'finals' => ?float,
      'overall' => ?float,  // average of the non-null term values above, or null if all three are null
  ]
  ```
  This exact shape is relied on by Task 4 (`GradesController@index` / the Grades view) and Task 5 (`ClassGradesExport`).

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/TermGradeCalculatorTest.php`:

```php
<?php

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\Term;
use App\Services\TermGradeCalculator;
use Illuminate\Support\Collection;

beforeEach(function () {
    Term::where('key', 'prelim')->update(['start_date' => '2026-01-01', 'end_date' => '2026-01-31']);
    Term::where('key', 'midterm')->update(['start_date' => '2026-02-01', 'end_date' => '2026-02-28']);
    Term::where('key', 'finals')->update(['start_date' => '2026-03-01', 'end_date' => '2026-03-31']);
});

function configuredTerms(): Collection
{
    return Term::whereIn('key', ['prelim', 'midterm', 'finals'])->get();
}

test('a student with sessions only in the prelim range gets a prelim score and null for the other terms', function () {
    $classRoom = ClassRoom::factory()->create();
    $student = Student::factory()->create();
    $classRoom->students()->attach($student->id);

    $session = AttendanceSession::factory()->create([
        'class_room_id' => $classRoom->id,
        'session_date' => '2026-01-15',
    ]);
    AttendanceRecord::factory()->create([
        'attendance_session_id' => $session->id,
        'student_id' => $student->id,
        'status' => 'present',
    ]);

    $row = (new TermGradeCalculator())->forClass($classRoom, configuredTerms())->firstWhere('student_id', $student->id);

    expect($row['prelim'])->toBe(100.0);
    expect($row['midterm'])->toBeNull();
    expect($row['finals'])->toBeNull();
    expect($row['overall'])->toBe(100.0);
});

test('a student with sessions in all three term ranges gets all three scores and a correct overall average', function () {
    $classRoom = ClassRoom::factory()->create();
    $student = Student::factory()->create();
    $classRoom->students()->attach($student->id);

    $prelimSession = AttendanceSession::factory()->create(['class_room_id' => $classRoom->id, 'session_date' => '2026-01-10']);
    $midtermSession = AttendanceSession::factory()->create(['class_room_id' => $classRoom->id, 'session_date' => '2026-02-10']);
    $finalsSession = AttendanceSession::factory()->create(['class_room_id' => $classRoom->id, 'session_date' => '2026-03-10']);

    AttendanceRecord::factory()->create(['attendance_session_id' => $prelimSession->id, 'student_id' => $student->id, 'status' => 'present']);
    AttendanceRecord::factory()->create(['attendance_session_id' => $midtermSession->id, 'student_id' => $student->id, 'status' => 'late']);
    AttendanceRecord::factory()->create(['attendance_session_id' => $finalsSession->id, 'student_id' => $student->id, 'status' => 'absent']);

    $row = (new TermGradeCalculator())->forClass($classRoom, configuredTerms())->firstWhere('student_id', $student->id);

    expect($row['prelim'])->toBe(100.0);
    expect($row['midterm'])->toBe(80.0);
    expect($row['finals'])->toBe(0.0);
    expect($row['overall'])->toBe(60.0);
});

test('a session outside all term ranges does not affect any term score or the overall', function () {
    $classRoom = ClassRoom::factory()->create();
    $student = Student::factory()->create();
    $classRoom->students()->attach($student->id);

    $outOfRangeSession = AttendanceSession::factory()->create([
        'class_room_id' => $classRoom->id,
        'session_date' => '2026-06-01',
    ]);
    AttendanceRecord::factory()->create([
        'attendance_session_id' => $outOfRangeSession->id,
        'student_id' => $student->id,
        'status' => 'absent',
    ]);

    $row = (new TermGradeCalculator())->forClass($classRoom, configuredTerms())->firstWhere('student_id', $student->id);

    expect($row['prelim'])->toBeNull();
    expect($row['midterm'])->toBeNull();
    expect($row['finals'])->toBeNull();
    expect($row['overall'])->toBeNull();
});

test('a real score of exactly 0 percent is distinct from no data', function () {
    $classRoom = ClassRoom::factory()->create();
    $student = Student::factory()->create();
    $classRoom->students()->attach($student->id);

    $session = AttendanceSession::factory()->create(['class_room_id' => $classRoom->id, 'session_date' => '2026-01-15']);
    AttendanceRecord::factory()->create(['attendance_session_id' => $session->id, 'student_id' => $student->id, 'status' => 'absent']);

    $row = (new TermGradeCalculator())->forClass($classRoom, configuredTerms())->firstWhere('student_id', $student->id);

    expect($row['prelim'])->toBe(0.0);
    expect($row['prelim'])->not->toBeNull();
    expect($row['midterm'])->toBeNull();
    expect($row['overall'])->toBe(0.0);
});

test('a term score never includes attendance from a different class the student is also enrolled in', function () {
    $classA = ClassRoom::factory()->create();
    $classB = ClassRoom::factory()->create();
    $student = Student::factory()->create();
    $classA->students()->attach($student->id);
    $classB->students()->attach($student->id);

    $sessionA = AttendanceSession::factory()->create(['class_room_id' => $classA->id, 'session_date' => '2026-01-10']);
    AttendanceRecord::factory()->create(['attendance_session_id' => $sessionA->id, 'student_id' => $student->id, 'status' => 'present']);

    $sessionB = AttendanceSession::factory()->create(['class_room_id' => $classB->id, 'session_date' => '2026-01-12']);
    AttendanceRecord::factory()->create(['attendance_session_id' => $sessionB->id, 'student_id' => $student->id, 'status' => 'absent']);

    $rowForClassA = (new TermGradeCalculator())->forClass($classA, configuredTerms())->firstWhere('student_id', $student->id);

    // Class A's only session was a 'present' (100). If class B's 'absent' record leaked in,
    // the average would drop to 50.
    expect($rowForClassA['prelim'])->toBe(100.0);
});

test('a class with no enrolled students returns an empty collection without error', function () {
    $classRoom = ClassRoom::factory()->create();

    $rows = (new TermGradeCalculator())->forClass($classRoom, configuredTerms());

    expect($rows)->toHaveCount(0);
});
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --compact --filter=TermGradeCalculatorTest`
Expected: FAIL — `Class "App\Services\TermGradeCalculator" not found`.

- [ ] **Step 3: Write the service**

Create `app/Services/TermGradeCalculator.php`:

```php
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
```

- [ ] **Step 4: Run the tests to verify they pass**

Run: `php artisan test --compact --filter=TermGradeCalculatorTest`
Expected: PASS, 6 tests.

- [ ] **Step 5: Format and commit**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add app/Services/TermGradeCalculator.php tests/Feature/TermGradeCalculatorTest.php
git commit -m "Add TermGradeCalculator for per-term, per-student attendance scoring"
```

---

### Task 3: Term settings page

**Files:**
- Create: `app/Http/Requests/UpdateTermsRequest.php`
- Create: `app/Http/Controllers/TermController.php`
- Create: `resources/views/terms/edit.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/TermControllerTest.php`

**Interfaces:**
- Consumes: `App\Models\Term` (Task 1).
- Produces: named routes `terms.edit` (GET `/terms`) and `terms.update` (PUT `/terms`), both behind `auth` middleware. Used by Task 4's "Configure Terms" link and Task 5's redirect-when-unconfigured.

- [ ] **Step 1: Write the failing tests**

Run: `php artisan make:test --pest TermControllerTest --no-interaction`

Replace `tests/Feature/TermControllerTest.php` with:

```php
<?php

use App\Models\Term;
use App\Models\User;

test('term settings page lists the three terms in a fixed order', function () {
    $response = $this->actingAs(User::factory()->create())->get(route('terms.edit'));

    $response->assertOk();
    $response->assertViewHas('terms', function ($terms) {
        return $terms->pluck('key')->all() === ['prelim', 'midterm', 'finals'];
    });
});

test('updating term dates saves all three terms', function () {
    $this->actingAs(User::factory()->create())->put(route('terms.update'), [
        'terms' => [
            'prelim' => ['start_date' => '2026-01-01', 'end_date' => '2026-01-31'],
            'midterm' => ['start_date' => '2026-02-01', 'end_date' => '2026-02-28'],
            'finals' => ['start_date' => '2026-03-01', 'end_date' => '2026-03-31'],
        ],
    ])->assertRedirect(route('terms.edit'));

    expect(Term::where('key', 'prelim')->first()->start_date->toDateString())->toBe('2026-01-01');
    expect(Term::where('key', 'finals')->first()->end_date->toDateString())->toBe('2026-03-31');
});

test('clearing a previously configured term back to empty dates is accepted', function () {
    Term::where('key', 'prelim')->update(['start_date' => '2026-01-01', 'end_date' => '2026-01-31']);

    $response = $this->actingAs(User::factory()->create())->put(route('terms.update'), [
        'terms' => [
            'prelim' => ['start_date' => '', 'end_date' => ''],
            'midterm' => ['start_date' => '', 'end_date' => ''],
            'finals' => ['start_date' => '', 'end_date' => ''],
        ],
    ]);

    $response->assertSessionDoesntHaveErrors();
    expect(Term::where('key', 'prelim')->first()->start_date)->toBeNull();
});

test('setting only a start date without an end date fails validation', function () {
    $response = $this->actingAs(User::factory()->create())->put(route('terms.update'), [
        'terms' => [
            'prelim' => ['start_date' => '2026-01-01', 'end_date' => ''],
            'midterm' => ['start_date' => '', 'end_date' => ''],
            'finals' => ['start_date' => '', 'end_date' => ''],
        ],
    ]);

    $response->assertSessionHasErrors('terms.prelim.end_date');
});

test('an end date before the start date fails validation', function () {
    $response = $this->actingAs(User::factory()->create())->put(route('terms.update'), [
        'terms' => [
            'prelim' => ['start_date' => '2026-01-31', 'end_date' => '2026-01-01'],
            'midterm' => ['start_date' => '', 'end_date' => ''],
            'finals' => ['start_date' => '', 'end_date' => ''],
        ],
    ]);

    $response->assertSessionHasErrors('terms.prelim.end_date');
});
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --compact --filter=TermControllerTest`
Expected: FAIL — route `terms.edit` not defined.

- [ ] **Step 3: Write the form request**

Run: `php artisan make:request UpdateTermsRequest --no-interaction`

Replace `app/Http/Requests/UpdateTermsRequest.php` with:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTermsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        $rules = [];

        foreach (['prelim', 'midterm', 'finals'] as $key) {
            $rules["terms.{$key}.start_date"] = ['nullable', 'date', "required_with:terms.{$key}.end_date"];
            $rules["terms.{$key}.end_date"] = ['nullable', 'date', "after_or_equal:terms.{$key}.start_date", "required_with:terms.{$key}.start_date"];
        }

        return $rules;
    }
}
```

- [ ] **Step 4: Write the controller**

Run: `php artisan make:controller TermController --no-interaction`

Replace `app/Http/Controllers/TermController.php` with:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateTermsRequest;
use App\Models\Term;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TermController extends Controller
{
    private const KEYS = ['prelim', 'midterm', 'finals'];

    public function edit(): View
    {
        $terms = collect(self::KEYS)->map(fn (string $key) => Term::where('key', $key)->first());

        return view('terms.edit', compact('terms'));
    }

    public function update(UpdateTermsRequest $request): RedirectResponse
    {
        foreach (self::KEYS as $key) {
            Term::where('key', $key)->update([
                'start_date' => $request->validated("terms.{$key}.start_date"),
                'end_date' => $request->validated("terms.{$key}.end_date"),
            ]);
        }

        return redirect()->route('terms.edit')->with('success', 'Term dates updated.');
    }
}
```

- [ ] **Step 5: Add the routes**

Modify `routes/web.php`: add the import and the two routes inside the existing `auth` middleware group.

```php
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TermController; // [tl! add]
use Illuminate\Support\Facades\Route;
```

```php
    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
    Route::get('/terms', [TermController::class, 'edit'])->name('terms.edit'); // [tl! add]
    Route::put('/terms', [TermController::class, 'update'])->name('terms.update'); // [tl! add]
});
```

- [ ] **Step 6: Write the view**

Create `resources/views/terms/edit.blade.php`:

```blade
@extends('layouts.app')

@section('title', 'Term Settings')

@section('content')

    @if (session('success'))
        <div class="mb-5 px-4 py-3 bg-present-50 border border-present/30 text-present text-sm rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-xl border border-slate-200 p-5 max-w-2xl">
        <h2 class="text-slate-700 font-semibold text-base mb-4">Configure Term Dates</h2>

        <form method="POST" action="{{ route('terms.update') }}" class="space-y-5">
            @csrf
            @method('PUT')

            @foreach ($terms as $term)
                <div class="grid grid-cols-3 gap-3 items-start">
                    <div class="pt-6">
                        <p class="text-sm font-medium text-slate-700">{{ $term->label }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1.5">Start Date</label>
                        <input type="date" name="terms[{{ $term->key }}][start_date]"
                               value="{{ old("terms.{$term->key}.start_date", optional($term->start_date)->format('Y-m-d')) }}"
                               class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-slate-800 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                        @error("terms.{$term->key}.start_date")
                            <p class="text-xs text-absent mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1.5">End Date</label>
                        <input type="date" name="terms[{{ $term->key }}][end_date]"
                               value="{{ old("terms.{$term->key}.end_date", optional($term->end_date)->format('Y-m-d')) }}"
                               class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-slate-800 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                        @error("terms.{$term->key}.end_date")
                            <p class="text-xs text-absent mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            @endforeach

            <button type="submit"
                    class="px-5 py-2 bg-primary hover:bg-primary-dark text-white text-sm font-semibold rounded-lg transition-colors">
                Save
            </button>
        </form>
    </div>
@endsection
```

- [ ] **Step 7: Run the tests to verify they pass**

Run: `php artisan test --compact --filter=TermControllerTest`
Expected: PASS, 5 tests.

- [ ] **Step 8: Format and commit**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add app/Http/Requests/UpdateTermsRequest.php app/Http/Controllers/TermController.php resources/views/terms/edit.blade.php routes/web.php tests/Feature/TermControllerTest.php
git commit -m "Add term settings page for configuring Prelim/Midterm/Finals dates"
```

---

### Task 4: Grades page

**Files:**
- Create: `app/Http/Controllers/GradesController.php`
- Create: `resources/views/grades/index.blade.php`
- Modify: `routes/web.php`
- Modify: `resources/views/layouts/app.blade.php:49-53`
- Test: `tests/Feature/GradesControllerTest.php`

**Interfaces:**
- Consumes: `App\Models\Term` (Task 1), `App\Services\TermGradeCalculator::forClass()` (Task 2, exact return shape as documented there), route `terms.edit` (Task 3, for the "Configure Terms" link).
- Produces: named route `grades.index` (GET `/grades`, `auth` middleware), used by Task 5 as the redirect target when export is blocked.

- [ ] **Step 1: Write the failing tests**

Run: `php artisan make:test --pest GradesControllerTest --no-interaction`

Replace `tests/Feature/GradesControllerTest.php` with:

```php
<?php

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;

test('grades page prompts to configure terms when any term date is missing', function () {
    $response = $this->actingAs(User::factory()->create())->get(route('grades.index'));

    $response->assertOk();
    $response->assertViewHas('termsConfigured', false);
    // The view's copy isn't rendered through Blade's escaping {{ }}, so the
    // apostrophe stays literal in the response — assert a substring without
    // one rather than guessing at entity-encoding.
    $response->assertSee('configured yet');
});

test('grades page prompts to select a class once terms are configured', function () {
    Term::where('key', 'prelim')->update(['start_date' => '2026-01-01', 'end_date' => '2026-01-31']);
    Term::where('key', 'midterm')->update(['start_date' => '2026-02-01', 'end_date' => '2026-02-28']);
    Term::where('key', 'finals')->update(['start_date' => '2026-03-01', 'end_date' => '2026-03-31']);

    $response = $this->actingAs(User::factory()->create())->get(route('grades.index'));

    $response->assertOk();
    $response->assertSee('Select a class to view grades.');
});

test('grades page shows the student grade table once terms are configured and a class is selected', function () {
    Term::where('key', 'prelim')->update(['start_date' => '2026-01-01', 'end_date' => '2026-01-31']);
    Term::where('key', 'midterm')->update(['start_date' => '2026-02-01', 'end_date' => '2026-02-28']);
    Term::where('key', 'finals')->update(['start_date' => '2026-03-01', 'end_date' => '2026-03-31']);

    $classRoom = ClassRoom::factory()->create();
    $student = Student::factory()->create(['fullname' => 'Grade Student']);
    $classRoom->students()->attach($student->id);

    $session = AttendanceSession::factory()->create(['class_room_id' => $classRoom->id, 'session_date' => '2026-01-15']);
    AttendanceRecord::factory()->create(['attendance_session_id' => $session->id, 'student_id' => $student->id, 'status' => 'present']);

    $response = $this->actingAs(User::factory()->create())
        ->get(route('grades.index', ['class_id' => $classRoom->id]));

    $response->assertOk();
    $response->assertSee('Grade Student');
    $response->assertViewHas('studentGrades', function ($rows) use ($student) {
        $row = $rows->firstWhere('student_id', $student->id);

        return $row['prelim'] === 100.0 && $row['midterm'] === null && $row['overall'] === 100.0;
    });
});

test('a term score of exactly zero renders as 0 percent, not as no data', function () {
    Term::where('key', 'prelim')->update(['start_date' => '2026-01-01', 'end_date' => '2026-01-31']);
    Term::where('key', 'midterm')->update(['start_date' => '2026-02-01', 'end_date' => '2026-02-28']);
    Term::where('key', 'finals')->update(['start_date' => '2026-03-01', 'end_date' => '2026-03-31']);

    $classRoom = ClassRoom::factory()->create();
    $student = Student::factory()->create();
    $classRoom->students()->attach($student->id);

    $session = AttendanceSession::factory()->create(['class_room_id' => $classRoom->id, 'session_date' => '2026-01-15']);
    AttendanceRecord::factory()->create(['attendance_session_id' => $session->id, 'student_id' => $student->id, 'status' => 'absent']);

    $response = $this->actingAs(User::factory()->create())
        ->get(route('grades.index', ['class_id' => $classRoom->id]));

    $response->assertOk();
    $response->assertSeeInOrder(['0%', '—']);
});

test('a class with no enrolled students renders an empty table without error', function () {
    Term::where('key', 'prelim')->update(['start_date' => '2026-01-01', 'end_date' => '2026-01-31']);
    Term::where('key', 'midterm')->update(['start_date' => '2026-02-01', 'end_date' => '2026-02-28']);
    Term::where('key', 'finals')->update(['start_date' => '2026-03-01', 'end_date' => '2026-03-31']);

    $classRoom = ClassRoom::factory()->create();

    $response = $this->actingAs(User::factory()->create())
        ->get(route('grades.index', ['class_id' => $classRoom->id]));

    $response->assertOk();
    $response->assertViewHas('studentGrades', fn ($rows) => $rows->isEmpty());
});

test('an invalid class_id returns a 404 instead of a server error', function () {
    Term::where('key', 'prelim')->update(['start_date' => '2026-01-01', 'end_date' => '2026-01-31']);
    Term::where('key', 'midterm')->update(['start_date' => '2026-02-01', 'end_date' => '2026-02-28']);
    Term::where('key', 'finals')->update(['start_date' => '2026-03-01', 'end_date' => '2026-03-31']);

    $response = $this->actingAs(User::factory()->create())
        ->get(route('grades.index', ['class_id' => 999999]));

    $response->assertNotFound();
});
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --compact --filter=GradesControllerTest`
Expected: FAIL — route `grades.index` not defined.

- [ ] **Step 3: Write the controller**

Run: `php artisan make:controller GradesController --no-interaction`

Replace `app/Http/Controllers/GradesController.php` with:

```php
<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Term;
use App\Services\TermGradeCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class GradesController extends Controller
{
    public function __construct(private readonly TermGradeCalculator $calculator) {}

    public function index(Request $request): View
    {
        $classRooms = ClassRoom::orderBy('name')->get(['id', 'name', 'section']);
        $terms = Term::whereIn('key', ['prelim', 'midterm', 'finals'])->get();
        $termsConfigured = $terms->every(fn (Term $term) => $term->start_date && $term->end_date);

        $selectedClassId = $request->input('class_id');
        $studentGrades = new Collection();

        if ($selectedClassId && $termsConfigured) {
            $classRoom = ClassRoom::findOrFail($selectedClassId);
            $studentGrades = $this->calculator->forClass($classRoom, $terms);
        }

        return view('grades.index', compact('classRooms', 'terms', 'termsConfigured', 'selectedClassId', 'studentGrades'));
    }
}
```

- [ ] **Step 4: Add the route**

Modify `routes/web.php`: add the import and route.

```php
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GradesController; // [tl! add]
use App\Http\Controllers\ReportsController;
```

```php
    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
    Route::get('/grades', [GradesController::class, 'index'])->name('grades.index'); // [tl! add]
    Route::get('/terms', [TermController::class, 'edit'])->name('terms.edit');
```

- [ ] **Step 5: Write the view**

Create `resources/views/grades/index.blade.php`:

```blade
@extends('layouts.app')

@section('title', 'Grades')

@section('content')

    @if (session('success'))
        <div class="mb-5 px-4 py-3 bg-present-50 border border-present/30 text-present text-sm rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-5 px-4 py-3 bg-absent-50 border border-absent/30 text-absent text-sm rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-xl border border-slate-200 p-5 mb-5">
        <form method="GET" action="{{ route('grades.index') }}" class="flex items-end gap-3 flex-wrap">
            <div class="flex-1 min-w-40">
                <label class="block text-xs font-medium text-slate-500 mb-1.5">Class</label>
                <select name="class_id"
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-slate-800 text-sm
                               focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                    <option value="">Select a class…</option>
                    @foreach ($classRooms as $classRoom)
                        <option value="{{ $classRoom->id }}" {{ (string) $selectedClassId === (string) $classRoom->id ? 'selected' : '' }}>
                            {{ $classRoom->name }}{{ $classRoom->section ? ' · ' . $classRoom->section : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit"
                    class="px-5 py-2 bg-primary hover:bg-primary-dark text-white text-sm font-semibold rounded-lg transition-colors">
                View
            </button>

            <a href="{{ route('terms.edit') }}"
               class="px-4 py-2 border border-slate-300 text-slate-600 text-sm font-medium rounded-lg hover:bg-slate-50 transition-colors">
                Configure Terms
            </a>

            @if ($selectedClassId && $termsConfigured && Route::has('grades.export'))
                <a href="{{ route('grades.export', ['class_id' => $selectedClassId]) }}"
                   class="px-4 py-2 border border-slate-300 text-slate-600 text-sm font-medium rounded-lg hover:bg-slate-50 transition-colors">
                    Export Excel
                </a>
            @endif
        </form>
    </div>

    @if (! $termsConfigured)
        <div class="bg-white rounded-xl border border-slate-200 p-10 text-center">
            <p class="text-slate-700 font-medium text-sm">Term dates aren't configured yet</p>
            <p class="text-slate-400 text-xs mt-1 mb-4">Set Prelim, Midterm, and Finals date ranges before viewing grades.</p>
            <a href="{{ route('terms.edit') }}"
               class="inline-block px-5 py-2 bg-primary hover:bg-primary-dark text-white text-sm font-semibold rounded-lg transition-colors">
                Configure Terms
            </a>
        </div>
    @elseif (! $selectedClassId)
        <div class="bg-white rounded-xl border border-slate-200 p-10 text-center">
            <p class="text-slate-400 text-sm">Select a class to view grades.</p>
        </div>
    @else
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-slate-400 bg-slate-50 border-b border-slate-100">
                        <th class="text-left px-5 py-3 font-medium">Student</th>
                        <th class="text-center px-4 py-3 font-medium">Prelim</th>
                        <th class="text-center px-4 py-3 font-medium">Midterm</th>
                        <th class="text-center px-4 py-3 font-medium">Finals</th>
                        <th class="text-center px-5 py-3 font-medium">Overall</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach ($studentGrades as $row)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <p class="font-medium text-slate-800">{{ $row['fullname'] }}</p>
                                <p class="text-xs text-slate-400">{{ $row['student_number'] }}</p>
                            </td>
                            <td class="px-4 py-3 text-center text-slate-700">{{ $row['prelim'] !== null ? $row['prelim'].'%' : '—' }}</td>
                            <td class="px-4 py-3 text-center text-slate-700">{{ $row['midterm'] !== null ? $row['midterm'].'%' : '—' }}</td>
                            <td class="px-4 py-3 text-center text-slate-700">{{ $row['finals'] !== null ? $row['finals'].'%' : '—' }}</td>
                            <td class="px-5 py-3 text-center font-semibold text-slate-800">{{ $row['overall'] !== null ? $row['overall'].'%' : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
```

Note: `route('grades.export', ...)` doesn't exist until Task 5, and Laravel's `route()` helper throws immediately for an unknown route name — so the `Route::has('grades.export')` guard above is required now, not an optional safety net. `GradesControllerTest`'s table-rendering test does select a configured class, which would otherwise hit this branch and throw.

- [ ] **Step 6: Run the tests to verify they pass**

Run: `php artisan test --compact --filter=GradesControllerTest`
Expected: PASS, 6 tests.

- [ ] **Step 7: Add the nav link**

Modify `resources/views/layouts/app.blade.php:49-53`:

```blade
                $navItems = [
                    ['route' => 'dashboard', 'label' => 'Dashboard'],
                    ['route' => 'classes.index', 'label' => 'My Classes'],
                    ['route' => 'attendance.index', 'label' => 'Attendance'],
                    ['route' => 'reports.index', 'label' => 'Reports'],
                    ['route' => 'grades.index', 'label' => 'Grades'],
                ];
```

- [ ] **Step 8: Format and commit**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add app/Http/Controllers/GradesController.php resources/views/grades/index.blade.php routes/web.php resources/views/layouts/app.blade.php tests/Feature/GradesControllerTest.php
git commit -m "Add Grades page showing per-term attendance grades for a selected class"
```

---

### Task 5: Excel export

**Files:**
- Create: `app/Exports/ClassGradesExport.php`
- Modify: `app/Http/Controllers/GradesController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/ClassGradesExportTest.php`

**Interfaces:**
- Consumes: `App\Services\TermGradeCalculator::forClass()` (Task 2), `App\Models\Term`, `App\Models\ClassRoom`, route `grades.index` (Task 4, as the redirect target when export is blocked).
- Produces: named route `grades.export` (GET `/grades/export`, `auth` middleware). Completes the `Route::has('grades.export')`-guarded link added in Task 4's view.

- [ ] **Step 1: Write the failing tests**

Run: `php artisan make:test --pest ClassGradesExportTest --no-interaction`

Replace `tests/Feature/ClassGradesExportTest.php` with:

```php
<?php

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;

test('exporting grades downloads an xlsx file when terms are configured', function () {
    Term::where('key', 'prelim')->update(['start_date' => '2026-01-01', 'end_date' => '2026-01-31']);
    Term::where('key', 'midterm')->update(['start_date' => '2026-02-01', 'end_date' => '2026-02-28']);
    Term::where('key', 'finals')->update(['start_date' => '2026-03-01', 'end_date' => '2026-03-31']);

    $classRoom = ClassRoom::factory()->create(['name' => 'Export Class']);
    $student = Student::factory()->create();
    $classRoom->students()->attach($student->id);

    $session = AttendanceSession::factory()->create(['class_room_id' => $classRoom->id, 'session_date' => '2026-01-15']);
    AttendanceRecord::factory()->create(['attendance_session_id' => $session->id, 'student_id' => $student->id, 'status' => 'present']);

    Excel::fake();

    $this->actingAs(User::factory()->create())
        ->get(route('grades.export', ['class_id' => $classRoom->id]))
        ->assertOk();

    Excel::assertDownloaded('Export Class-grades.xlsx');
});

test('exporting grades redirects back with an error when terms are not configured', function () {
    $classRoom = ClassRoom::factory()->create();

    $response = $this->actingAs(User::factory()->create())
        ->get(route('grades.export', ['class_id' => $classRoom->id]));

    $response->assertRedirect(route('grades.index', ['class_id' => $classRoom->id]));
    $response->assertSessionHas('error');
});

test('an invalid class_id returns a 404 instead of a server error', function () {
    Term::where('key', 'prelim')->update(['start_date' => '2026-01-01', 'end_date' => '2026-01-31']);
    Term::where('key', 'midterm')->update(['start_date' => '2026-02-01', 'end_date' => '2026-02-28']);
    Term::where('key', 'finals')->update(['start_date' => '2026-03-01', 'end_date' => '2026-03-31']);

    $response = $this->actingAs(User::factory()->create())
        ->get(route('grades.export', ['class_id' => 999999]));

    $response->assertNotFound();
});
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --compact --filter=ClassGradesExportTest`
Expected: FAIL — route `grades.export` not defined.

- [ ] **Step 3: Write the export class**

Run: `php artisan make:export ClassGradesExport --model=Student --no-interaction` — if this scaffolds a model-bound export shape you don't need, that's fine; replace the whole file's contents in the next step regardless.

Replace `app/Exports/ClassGradesExport.php` with:

```php
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

        return (new TermGradeCalculator())->forClass($this->classRoom, $terms);
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
```

- [ ] **Step 4: Add the export action and route**

Modify `app/Http/Controllers/GradesController.php` — add the `export` method and imports:

```php
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

class GradesController extends Controller
{
    public function __construct(private readonly TermGradeCalculator $calculator) {}

    public function index(Request $request): View
    {
        $classRooms = ClassRoom::orderBy('name')->get(['id', 'name', 'section']);
        $terms = Term::whereIn('key', ['prelim', 'midterm', 'finals'])->get();
        $termsConfigured = $terms->every(fn (Term $term) => $term->start_date && $term->end_date);

        $selectedClassId = $request->input('class_id');
        $studentGrades = new Collection();

        if ($selectedClassId && $termsConfigured) {
            $classRoom = ClassRoom::findOrFail($selectedClassId);
            $studentGrades = $this->calculator->forClass($classRoom, $terms);
        }

        return view('grades.index', compact('classRooms', 'terms', 'termsConfigured', 'selectedClassId', 'studentGrades'));
    }

    public function export(Request $request): RedirectResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
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
```

Modify `routes/web.php`:

```php
    Route::get('/grades', [GradesController::class, 'index'])->name('grades.index');
    Route::get('/grades/export', [GradesController::class, 'export'])->name('grades.export'); // [tl! add]
    Route::get('/terms', [TermController::class, 'edit'])->name('terms.edit');
```

- [ ] **Step 5: Run the tests to verify they pass**

Run: `php artisan test --compact --filter=ClassGradesExportTest`
Expected: PASS, 3 tests.

- [ ] **Step 6: Run the full suite**

Run: `php artisan test --compact`
Expected: all tests pass except the pre-existing, unrelated `ExampleTest` failure (`GET /` → 302 vs expected 200 — out of scope per Global Constraints).

- [ ] **Step 7: Format and commit**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add app/Exports/ClassGradesExport.php app/Http/Controllers/GradesController.php routes/web.php tests/Feature/ClassGradesExportTest.php
git commit -m "Add Excel export for per-class term grades"
```
