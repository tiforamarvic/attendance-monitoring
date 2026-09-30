<?php

use App\Models\Term;
use Illuminate\Support\Carbon;

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

    expect($prelim->start_date)->toBeInstanceOf(Carbon::class);
    expect($prelim->start_date->toDateString())->toBe('2026-01-01');
    expect($prelim->end_date->toDateString())->toBe('2026-01-31');
});
