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
