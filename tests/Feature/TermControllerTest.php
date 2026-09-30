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
