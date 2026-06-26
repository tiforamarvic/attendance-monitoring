@extends('layouts.app')

@section('title', $classRoom->name)

@section('header-actions')
    <a href="{{ route('classes.index') }}"
       class="px-4 py-2 border border-slate-300 text-slate-600 text-sm font-medium rounded-lg hover:bg-slate-50 transition-colors">
        ← Back
    </a>
    <a href="{{ route('classes.edit', $classRoom) }}"
       class="px-4 py-2 border border-slate-300 text-slate-600 text-sm font-medium rounded-lg hover:bg-slate-50 transition-colors">
        Edit Class
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

    <div class="grid grid-cols-3 gap-5">

        {{-- Class info --}}
        <div class="col-span-1 space-y-4">
            <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-4">
                <div>
                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wide mb-1">Class Name</p>
                    <p class="text-slate-800 font-semibold">{{ $classRoom->name }}</p>
                </div>

                @if ($classRoom->code)
                    <div>
                        <p class="text-xs font-medium text-slate-400 uppercase tracking-wide mb-1">Course Code</p>
                        <p class="text-slate-700 text-sm">{{ $classRoom->code }}</p>
                    </div>
                @endif

                @if ($classRoom->section)
                    <div>
                        <p class="text-xs font-medium text-slate-400 uppercase tracking-wide mb-1">Section</p>
                        <p class="text-slate-700 text-sm">{{ $classRoom->section }}</p>
                    </div>
                @endif

                @if ($classRoom->description)
                    <div>
                        <p class="text-xs font-medium text-slate-400 uppercase tracking-wide mb-1">Description</p>
                        <p class="text-slate-600 text-sm">{{ $classRoom->description }}</p>
                    </div>
                @endif

                @if ($classRoom->schedules->isNotEmpty())
                    <div>
                        <p class="text-xs font-medium text-slate-400 uppercase tracking-wide mb-2">Schedule</p>
                        <div class="space-y-1.5">
                            @foreach ($classRoom->schedules->sortBy(fn ($s) => array_search($s->day_of_week, ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'])) as $schedule)
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="capitalize font-medium text-slate-700 w-20">{{ $schedule->day_of_week }}</span>
                                    @if ($schedule->start_time)
                                        <span class="text-slate-400">
                                            {{ \Carbon\Carbon::parse($schedule->start_time)->format('g:i A') }}
                                            @if ($schedule->end_time)
                                                – {{ \Carbon\Carbon::parse($schedule->end_time)->format('g:i A') }}
                                            @endif
                                        </span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Students --}}
        <div class="col-span-2">
            <div class="bg-white rounded-xl border border-slate-200 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-slate-700 font-semibold text-base">
                        Students
                        <span class="ml-1 text-xs font-normal text-slate-400">{{ $classRoom->students->count() }}</span>
                    </h2>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('classes.students.import', $classRoom) }}"
                           class="px-3 py-1.5 border border-slate-300 text-slate-600 text-xs font-medium rounded-lg hover:bg-slate-50 transition-colors">
                            Import Excel / CSV
                        </a>
                        <a href="{{ route('classes.students.create', $classRoom) }}"
                           class="px-3 py-1.5 bg-primary text-white text-xs font-medium rounded-lg hover:bg-primary-dark transition-colors">
                            + Add Student
                        </a>
                    </div>
                </div>

                @if ($classRoom->students->isEmpty())
                    <div class="py-10 text-center">
                        <p class="text-slate-400 text-sm">No students enrolled yet.</p>
                        <p class="text-slate-300 text-xs mt-1">Add students manually or import from a file.</p>
                    </div>
                @else
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs text-slate-400 border-b border-slate-100">
                                <th class="text-left pb-2.5 font-medium">Student No.</th>
                                <th class="text-left pb-2.5 font-medium">Full Name</th>
                                <th class="text-left pb-2.5 font-medium">Email</th>
                                <th class="pb-2.5"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @foreach ($classRoom->students->sortBy('fullname') as $student)
                                <tr class="hover:bg-slate-50 group">
                                    <td class="py-2.5 text-slate-500 text-xs">{{ $student->student_number }}</td>
                                    <td class="py-2.5 text-slate-800 font-medium">{{ $student->fullname }}</td>
                                    <td class="py-2.5 text-slate-400 text-xs">{{ $student->email ?? '—' }}</td>
                                    <td class="py-2.5 text-right">
                                        <div class="flex items-center justify-end gap-3 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <form method="POST"
                                                  action="{{ route('classes.students.destroy', [$classRoom, $student]) }}"
                                                  data-confirm
                                                  data-confirm-title="Remove Student"
                                                  data-confirm-message="Remove &quot;{{ addslashes($student->fullname) }}&quot; from this class? Their record and attendance history will remain intact."
                                                  data-confirm-ok="Remove">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs text-slate-400 hover:text-slate-600 hover:underline">
                                                    Remove
                                                </button>
                                            </form>
                                            <form method="POST"
                                                  action="{{ route('classes.students.delete', [$classRoom, $student]) }}"
                                                  data-confirm
                                                  data-confirm-danger
                                                  data-confirm-title="Permanently Delete Student"
                                                  data-confirm-message="Delete &quot;{{ addslashes($student->fullname) }}&quot; permanently? This removes them from ALL classes and erases ALL their attendance records. This cannot be undone."
                                                  data-confirm-ok="Delete Permanently">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs text-absent hover:underline">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
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
