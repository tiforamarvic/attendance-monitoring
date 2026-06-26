@extends('layouts.app')

@section('title', 'Take Attendance')

@section('content')

    {{-- Step 1: Class + Date selector --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5 mb-5">
        <h2 class="text-slate-700 font-semibold text-base mb-4">Select Class & Date</h2>

        <form method="GET" action="{{ route('attendance.create') }}" class="flex items-end gap-3">
            <div class="flex-1">
                <label for="class_id" class="block text-sm font-medium text-slate-700 mb-1.5">Class</label>
                <select id="class_id" name="class_id"
                        class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-slate-800 text-sm
                               focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                    <option value="">— Select a class —</option>
                    @foreach ($classRooms as $classRoom)
                        <option value="{{ $classRoom->id }}"
                                {{ optional($selectedClass)->id == $classRoom->id ? 'selected' : '' }}>
                            {{ $classRoom->name }}
                            @if ($classRoom->section) ({{ $classRoom->section }}) @endif
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="date" class="block text-sm font-medium text-slate-700 mb-1.5">Date</label>
                <input type="date" id="date" name="date" value="{{ $date }}"
                       class="px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-slate-800 text-sm
                              focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
            </div>

            <button type="submit"
                    class="px-5 py-2 bg-primary hover:bg-primary-dark text-white text-sm font-semibold rounded-lg transition-colors">
                Load Students
            </button>
        </form>
    </div>

    {{-- Step 2: Student list (shown after class is selected) --}}
    @if ($selectedClass)

        @if ($existingSession)
            <div class="mb-5 px-4 py-3 bg-late-50 border border-late/30 text-late text-sm rounded-lg flex items-center justify-between">
                <span>A session for <strong>{{ $selectedClass->name }}</strong> on this date already exists.</span>
                <a href="{{ route('attendance.show', $existingSession) }}"
                   class="font-semibold underline ml-3">View / Edit it →</a>
            </div>
        @else
            <form method="POST" action="{{ route('attendance.store') }}">
                @csrf
                <input type="hidden" name="class_room_id" value="{{ $selectedClass->id }}">
                <input type="hidden" name="session_date" value="{{ $date }}">

                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden mb-4">
                    {{-- Session header --}}
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <p class="font-semibold text-slate-800">{{ $selectedClass->name }}
                                @if ($selectedClass->section)
                                    <span class="text-slate-400 font-normal">· {{ $selectedClass->section }}</span>
                                @endif
                            </p>
                            <p class="text-sm text-slate-400 mt-0.5">
                                {{ \Carbon\Carbon::parse($date)->format('l, F j, Y') }}
                                · {{ $students->count() }} student{{ $students->count() !== 1 ? 's' : '' }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2 text-xs text-slate-400">
                            <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg bg-present-50 text-present font-medium">● Present</span>
                            <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg bg-late-50 text-late font-medium">● Late</span>
                            <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg bg-absent-50 text-absent font-medium">● Absent</span>
                            <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg bg-excused-50 text-excused font-medium">● Excused</span>
                        </div>
                    </div>

                    @if ($students->isEmpty())
                        <div class="py-12 text-center">
                            <p class="text-slate-400 text-sm">No students enrolled in this class yet.</p>
                            <a href="{{ route('classes.students.create', $selectedClass) }}"
                               class="text-primary text-sm font-medium hover:underline mt-1 inline-block">
                                Add students →
                            </a>
                        </div>
                    @else
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
                                @foreach ($students as $i => $student)
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-5 py-3 text-slate-400 text-xs">{{ $i + 1 }}</td>
                                        <td class="px-5 py-3">
                                            <p class="font-medium text-slate-800">{{ $student->fullname }}</p>
                                            <p class="text-xs text-slate-400">{{ $student->student_number }}</p>
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
                                                               name="attendance[{{ $student->id }}][status]"
                                                               value="{{ $status }}"
                                                               {{ $status === 'present' ? 'checked' : '' }}
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
                                                   name="attendance[{{ $student->id }}][remarks]"
                                                   placeholder="Optional note…"
                                                   class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-600
                                                          focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                @if ($students->isNotEmpty())
                    <div class="flex items-center gap-3">
                        <button type="submit"
                                class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white text-sm font-semibold rounded-lg transition-colors">
                            Save Attendance
                        </button>
                        <a href="{{ route('attendance.index') }}"
                           class="px-5 py-2.5 border border-slate-300 text-slate-600 text-sm font-medium rounded-lg hover:bg-slate-50 transition-colors">
                            Cancel
                        </a>
                    </div>
                @endif
            </form>
        @endif

    @endif
@endsection
