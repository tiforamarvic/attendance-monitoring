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
