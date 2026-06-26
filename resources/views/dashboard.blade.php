@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

    {{-- Stat cards --}}
    <div class="grid grid-cols-4 gap-5 mb-6">
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-slate-500 text-sm mb-3">Total Classes</p>
            <p class="text-3xl font-bold text-slate-800">{{ $totalClasses }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-slate-500 text-sm mb-3">Total Students</p>
            <p class="text-3xl font-bold text-slate-800">{{ $totalStudents }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-slate-500 text-sm mb-3">Sessions Today</p>
            <p class="text-3xl font-bold text-slate-800">{{ $sessionsToday }}</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-slate-500 text-sm mb-3">Avg. Attendance Score</p>
            <p class="text-3xl font-bold text-slate-800">
                {{ $avgAttendance !== null ? number_format($avgAttendance, 1).'%' : '—' }}
            </p>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-5">

        {{-- Recent sessions --}}
        <div class="col-span-1">
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <h2 class="text-slate-700 font-semibold text-base mb-4">Recent Sessions</h2>

                @if ($recentSessions->isEmpty())
                    <p class="text-slate-400 text-sm text-center py-8">No sessions recorded yet.</p>
                @else
                    <div class="space-y-2">
                        @foreach ($recentSessions as $session)
                            <div class="flex items-center justify-between py-2 border-b border-slate-50 last:border-0">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-slate-700 truncate">{{ $session->classRoom->name }}</p>
                                    @if ($session->classRoom->section)
                                        <p class="text-xs text-slate-400">{{ $session->classRoom->section }}</p>
                                    @endif
                                </div>
                                <span class="text-xs text-slate-400 flex-shrink-0 ml-2">
                                    {{ $session->session_date->format('M j') }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Poor attendance tracker --}}
        <div class="col-span-2">
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-slate-700 font-semibold text-base">Poor Attendance</h2>
                        <p class="text-slate-400 text-xs mt-0.5">Students with average score below 75%</p>
                    </div>
                    @if ($poorAttendanceStudents->isNotEmpty())
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-absent-50 text-absent text-xs font-semibold">
                            {{ $poorAttendanceStudents->count() }} student{{ $poorAttendanceStudents->count() !== 1 ? 's' : '' }}
                        </span>
                    @endif
                </div>

                @if ($poorAttendanceStudents->isEmpty())
                    <div class="py-10 text-center">
                        <p class="text-present text-sm font-medium">All students are on track.</p>
                        <p class="text-slate-400 text-xs mt-1">No student has an average below 75%.</p>
                    </div>
                @else
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs text-slate-400 border-b border-slate-100">
                                <th class="text-left pb-2.5 font-medium">Student</th>
                                <th class="text-left pb-2.5 font-medium">Class</th>
                                <th class="text-right pb-2.5 font-medium">Absences</th>
                                <th class="text-right pb-2.5 font-medium">Avg. Score</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @foreach ($poorAttendanceStudents as $row)
                                <tr class="hover:bg-slate-50">
                                    <td class="py-2.5">
                                        <p class="font-medium text-slate-800">{{ $row->fullname }}</p>
                                        <p class="text-xs text-slate-400">{{ $row->student_number }}</p>
                                    </td>
                                    <td class="py-2.5">
                                        <p class="text-slate-600 text-sm">{{ $row->class_name }}</p>
                                        @if ($row->class_section)
                                            <p class="text-xs text-slate-400">{{ $row->class_section }}</p>
                                        @endif
                                    </td>
                                    <td class="py-2.5 text-right">
                                        <span class="text-sm font-medium text-absent">{{ $row->absent_count }}</span>
                                        <span class="text-xs text-slate-400"> / {{ $row->total_sessions }}</span>
                                    </td>
                                    <td class="py-2.5 text-right">
                                        @php $score = round($row->average_score, 1); @endphp
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold
                                            {{ $score < 50 ? 'bg-absent-50 text-absent' : 'bg-late-50 text-late' }}">
                                            {{ $score }}%
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

    </div>
@endsection
