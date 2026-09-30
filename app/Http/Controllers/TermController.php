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
