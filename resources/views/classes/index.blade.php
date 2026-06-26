@extends('layouts.app')

@section('title', 'My Classes')

@section('header-actions')
    <a href="{{ route('classes.create') }}"
       class="px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary-dark transition-colors">
        + Create Class
    </a>
@endsection

@section('content')
    @if (session('success'))
        <div class="mb-5 px-4 py-3 bg-present-50 border border-present/30 text-present text-sm rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if ($classRooms->isEmpty())
        <div class="bg-white rounded-xl border border-slate-200 p-14 text-center">
            <p class="text-slate-400 text-sm mb-3">No classes yet.</p>
            <a href="{{ route('classes.create') }}" class="text-primary text-sm font-medium hover:underline">
                Create your first class →
            </a>
        </div>
    @else
        <div class="grid grid-cols-3 gap-5">
            @foreach ($classRooms as $classRoom)
                <div class="bg-white rounded-xl border border-slate-200 p-5 flex flex-col gap-4">

                    {{-- Class header --}}
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="text-slate-800 font-semibold text-base leading-tight">{{ $classRoom->name }}</h2>
                            @if ($classRoom->code || $classRoom->section)
                                <p class="text-slate-400 text-xs mt-0.5">
                                    {{ collect([$classRoom->code, $classRoom->section])->filter()->implode(' · ') }}
                                </p>
                            @endif
                        </div>
                        <span class="inline-flex items-center text-xs font-medium bg-primary-50 text-primary px-2.5 py-1 rounded-lg flex-shrink-0 whitespace-nowrap">
                            {{ $classRoom->students_count }}
                            {{ $classRoom->students_count === 1 ? 'student' : 'students' }}
                        </span>
                    </div>

                    {{-- Schedule --}}
                    <div class="flex-1">
                        @if ($classRoom->schedules->isNotEmpty())
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
                                        @else
                                            <span class="text-slate-300">—</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-slate-400">No schedule set.</p>
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-3 pt-3 border-t border-slate-100">
                        <a href="{{ route('classes.show', $classRoom) }}"
                           class="text-xs text-primary font-medium hover:underline">View</a>
                        <a href="{{ route('classes.edit', $classRoom) }}"
                           class="text-xs text-slate-500 hover:text-slate-800 font-medium">Edit</a>
                        <form method="POST" action="{{ route('classes.destroy', $classRoom) }}" class="ml-auto"
                              data-confirm
                              data-confirm-danger
                              data-confirm-title="Delete Class"
                              data-confirm-message="Delete &quot;{{ addslashes($classRoom->name) }}&quot;? This will permanently remove the class and all its attendance records."
                              data-confirm-ok="Delete Class">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="text-xs text-absent hover:underline font-medium">Delete</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
