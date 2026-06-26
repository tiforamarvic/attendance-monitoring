@extends('layouts.app')

@section('title', 'Reports')

@section('content')

    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5 mb-5">
        <form method="GET" action="{{ route('reports.index') }}" class="flex items-end gap-3 flex-wrap">
            <div class="flex-1 min-w-40">
                <label class="block text-xs font-medium text-slate-500 mb-1.5">Class</label>
                <select name="class_id"
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-slate-800 text-sm
                               focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                    <option value="">All Classes</option>
                    @foreach ($classRooms as $classRoom)
                        <option value="{{ $classRoom->id }}" {{ $selectedClassId == $classRoom->id ? 'selected' : '' }}>
                            {{ $classRoom->name }}{{ $classRoom->section ? ' · ' . $classRoom->section : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1.5">Failing Threshold</label>
                <select name="threshold"
                        class="px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-slate-800 text-sm
                               focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                    @foreach ([50 => 'Below 50%', 60 => 'Below 60%', 75 => 'Below 75%', 80 => 'Below 80%'] as $val => $label)
                        <option value="{{ $val }}" {{ $threshold == $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit"
                    class="px-5 py-2 bg-primary hover:bg-primary-dark text-white text-sm font-semibold rounded-lg transition-colors">
                Generate
            </button>

            @if ($selectedClassId || $threshold != 75)
                <a href="{{ route('reports.index') }}"
                   class="px-4 py-2 border border-slate-300 text-slate-500 text-sm rounded-lg hover:bg-slate-50 transition-colors">
                    Reset
                </a>
            @endif
        </form>
    </div>

    {{-- Summary cards --}}
    <div class="grid grid-cols-3 gap-4 mb-5">
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs font-medium text-slate-400 uppercase tracking-wide mb-1">Failing Students</p>
            <p class="text-3xl font-bold text-absent">{{ $summary['total'] }}</p>
            <p class="text-xs text-slate-400 mt-1">below {{ $threshold }}% attendance</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs font-medium text-slate-400 uppercase tracking-wide mb-1">Critical (Below 50%)</p>
            <p class="text-3xl font-bold text-slate-800">{{ $summary['critical'] }}</p>
            <p class="text-xs text-slate-400 mt-1">severely at risk</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-xs font-medium text-slate-400 uppercase tracking-wide mb-1">Classes Affected</p>
            <p class="text-3xl font-bold text-slate-800">{{ $summary['classes'] }}</p>
            <p class="text-xs text-slate-400 mt-1">with failing students</p>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-slate-700 font-semibold text-base">
                Failing Students
                <span class="ml-1.5 text-xs font-normal text-slate-400">below {{ $threshold }}%</span>
            </h2>
            @if ($selectedClassId)
                <span class="text-xs text-slate-400">
                    Filtered by: <span class="font-medium text-slate-600">{{ $classRooms->firstWhere('id', $selectedClassId)?->name }}</span>
                </span>
            @endif
        </div>

        @if ($failingStudents->isEmpty())
            <div class="py-16 text-center">
                <div class="w-12 h-12 bg-present-50 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-present" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <p class="text-slate-700 font-medium text-sm">No failing students found</p>
                <p class="text-slate-400 text-xs mt-1">All students are above the {{ $threshold }}% threshold.</p>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-slate-400 bg-slate-50 border-b border-slate-100">
                        <th class="text-left px-5 py-3 font-medium">Student</th>
                        <th class="text-left px-5 py-3 font-medium">Class</th>
                        <th class="text-center px-4 py-3 font-medium">Sessions</th>
                        <th class="text-center px-4 py-3 font-medium">Present</th>
                        <th class="text-center px-4 py-3 font-medium">Late</th>
                        <th class="text-center px-4 py-3 font-medium">Absent</th>
                        <th class="text-center px-4 py-3 font-medium">Excused</th>
                        <th class="text-center px-5 py-3 font-medium">Rate</th>
                        <th class="text-center px-5 py-3 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach ($failingStudents as $row)
                        @php
                            $rate = (float) $row->attendance_rate;
                            $isCritical = $rate < 50;
                        @endphp
                        <tr class="hover:bg-slate-50 {{ $isCritical ? 'bg-absent-50/30' : '' }}">
                            <td class="px-5 py-3">
                                <p class="font-medium text-slate-800">{{ $row->fullname }}</p>
                                <p class="text-xs text-slate-400">{{ $row->student_number }}</p>
                            </td>
                            <td class="px-5 py-3">
                                <p class="text-slate-700">{{ $row->class_name }}</p>
                                @if ($row->class_section)
                                    <p class="text-xs text-slate-400">{{ $row->class_section }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-slate-600">{{ $row->total_sessions }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="text-present font-medium">{{ $row->present_count }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="text-late font-medium">{{ $row->late_count }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="text-absent font-medium">{{ $row->absent_count }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="text-excused font-medium">{{ $row->excused_count }}</span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                @php
                                    $rateColor = $rate < 50
                                        ? 'text-absent font-bold'
                                        : ($rate < 65 ? 'text-late font-semibold' : 'text-slate-700 font-semibold');
                                @endphp
                                <span class="{{ $rateColor }}">{{ $rate }}%</span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                @if ($isCritical)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-absent text-white">
                                        Critical
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-late-50 text-late">
                                        At Risk
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
