@extends('layouts.app')

@section('title', $attendanceSession->classRoom->name . ' – ' . $attendanceSession->session_date->format('M j, Y'))

@section('header-actions')
    <a href="{{ route('attendance.index') }}"
       class="px-4 py-2 border border-slate-300 text-slate-600 text-sm font-medium rounded-lg hover:bg-slate-50 transition-colors">
        ← Back
    </a>
@endsection

@section('content')
    @if (session('success'))
        <div class="mb-5 px-4 py-3 bg-present-50 border border-present/30 text-present text-sm rounded-lg">
            {{ session('success') }}
        </div>
    @endif
    @if (session('warning'))
        <div class="mb-5 px-4 py-3 bg-late-50 border border-late/30 text-late text-sm rounded-lg">
            {{ session('warning') }}
        </div>
    @endif

    <form method="POST" action="{{ route('attendance.update', $attendanceSession) }}">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden mb-4">
            {{-- Session header --}}
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <p class="font-semibold text-slate-800">
                        {{ $attendanceSession->classRoom->name }}
                        @if ($attendanceSession->classRoom->section)
                            <span class="text-slate-400 font-normal">· {{ $attendanceSession->classRoom->section }}</span>
                        @endif
                    </p>
                    <p class="text-sm text-slate-400 mt-0.5">
                        {{ $attendanceSession->session_date->format('l, F j, Y') }}
                        · {{ $attendanceSession->attendanceRecords->count() }} student{{ $attendanceSession->attendanceRecords->count() !== 1 ? 's' : '' }}
                    </p>
                </div>

                {{-- Summary badges --}}
                <div class="flex items-center gap-2 text-xs">
                    @php
                        $counts = $attendanceSession->attendanceRecords->countBy('status');
                    @endphp
                    @foreach (['present' => 'text-present bg-present-50', 'late' => 'text-late bg-late-50', 'absent' => 'text-absent bg-absent-50', 'excused' => 'text-excused bg-excused-50'] as $status => $cls)
                        @if (($counts[$status] ?? 0) > 0)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg font-semibold {{ $cls }}">
                                {{ $counts[$status] }} {{ ucfirst($status) }}
                            </span>
                        @endif
                    @endforeach
                </div>
            </div>

            {{-- Student table --}}
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-slate-400 bg-slate-50 border-b border-slate-100">
                        <th class="text-left px-5 py-2.5 font-medium w-8">#</th>
                        <th class="text-left px-5 py-2.5 font-medium">Student</th>
                        <th class="px-5 py-2.5 font-medium text-center">Status</th>
                        <th class="text-left px-5 py-2.5 font-medium">Remarks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach ($attendanceSession->attendanceRecords->sortBy('student.fullname') as $i => $record)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 text-slate-400 text-xs">{{ $i + 1 }}</td>
                            <td class="px-5 py-3">
                                <p class="font-medium text-slate-800">{{ $record->student->fullname }}</p>
                                <p class="text-xs text-slate-400">{{ $record->student->student_number }}</p>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-center gap-1.5">
                                    @foreach ([
                                        'present' => ['label' => 'Present', 'hover' => 'hover:bg-present-50', 'checked' => 'peer-checked:bg-present peer-checked:text-white peer-checked:border-transparent'],
                                        'late'    => ['label' => 'Late',    'hover' => 'hover:bg-late-50',    'checked' => 'peer-checked:bg-late peer-checked:text-white peer-checked:border-transparent'],
                                        'absent'  => ['label' => 'Absent',  'hover' => 'hover:bg-absent-50',  'checked' => 'peer-checked:bg-absent peer-checked:text-white peer-checked:border-transparent'],
                                        'excused' => ['label' => 'Excused', 'hover' => 'hover:bg-excused-50', 'checked' => 'peer-checked:bg-excused peer-checked:text-white peer-checked:border-transparent'],
                                    ] as $status => $cfg)
                                        <label class="cursor-pointer">
                                            <input type="radio"
                                                   name="attendance[{{ $record->student_id }}][status]"
                                                   value="{{ $status }}"
                                                   {{ $record->status === $status ? 'checked' : '' }}
                                                   class="sr-only peer">
                                            <span class="px-3 py-1.5 rounded-lg text-xs font-bold border border-slate-500 transition-colors
                                                         bg-white text-slate-900 {{ $cfg['hover'] }} {{ $cfg['checked'] }}">
                                                {{ $cfg['label'] }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <input type="text"
                                       name="attendance[{{ $record->student_id }}][remarks]"
                                       value="{{ $record->remarks }}"
                                       placeholder="Optional note…"
                                       class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-600
                                              focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit"
                    class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white text-sm font-semibold rounded-lg transition-colors">
                Save Changes
            </button>
            <a href="{{ route('attendance.index') }}"
               class="px-5 py-2.5 border border-slate-300 text-slate-600 text-sm font-medium rounded-lg hover:bg-slate-50 transition-colors">
                Cancel
            </a>
        </div>
    </form>
@endsection
