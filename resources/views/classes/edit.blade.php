@extends('layouts.app')

@section('title', 'Edit Class')

@section('content')
    <div class="max-w-2xl">
        <form method="POST" action="{{ route('classes.update', $classRoom) }}">
            @csrf
            @method('PUT')

            {{-- Class Details --}}
            <div class="bg-white rounded-xl border border-slate-200 p-6 mb-5">
                <h2 class="text-slate-700 font-semibold text-base mb-5">Class Details</h2>

                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700 mb-1.5">
                            Class Name <span class="text-absent">*</span>
                        </label>
                        <input type="text" id="name" name="name" value="{{ old('name', $classRoom->name) }}"
                               class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-slate-800 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary
                                      @error('name') border-absent @enderror"
                               placeholder="e.g. Introduction to Programming">
                        @error('name')
                            <p class="mt-1.5 text-xs text-absent">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="code" class="block text-sm font-medium text-slate-700 mb-1.5">Course Code</label>
                            <input type="text" id="code" name="code" value="{{ old('code', $classRoom->code) }}"
                                   class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-slate-800 text-sm
                                          focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
                                   placeholder="e.g. CS101">
                        </div>
                        <div>
                            <label for="section" class="block text-sm font-medium text-slate-700 mb-1.5">Section</label>
                            <input type="text" id="section" name="section" value="{{ old('section', $classRoom->section) }}"
                                   class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-slate-800 text-sm
                                          focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
                                   placeholder="e.g. A">
                        </div>
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-slate-700 mb-1.5">Description</label>
                        <textarea id="description" name="description" rows="3"
                                  class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-slate-800 text-sm
                                         focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary resize-none"
                                  placeholder="Optional notes about this class">{{ old('description', $classRoom->description) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Schedule --}}
            <div class="bg-white rounded-xl border border-slate-200 p-6 mb-5">
                <h2 class="text-slate-700 font-semibold text-base mb-1">Schedule</h2>
                <p class="text-slate-400 text-xs mb-5">Check the days this class meets and set times (optional).</p>

                @php
                    $days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
                @endphp

                <div class="divide-y divide-slate-100">
                    @foreach ($days as $day)
                        @php
                            $existingSchedule = $schedulesByDay->get($day);
                        @endphp
                        <div class="flex items-center gap-4 py-3">
                            <label class="flex items-center gap-2.5 w-32 cursor-pointer select-none">
                                <input type="checkbox" name="schedule_days[]" value="{{ $day }}"
                                       {{ in_array($day, old('schedule_days', $enabledDays)) ? 'checked' : '' }}
                                       class="w-4 h-4 rounded accent-primary">
                                <span class="text-sm font-medium text-slate-700 capitalize">{{ $day }}</span>
                            </label>
                            <div class="flex items-center gap-2">
                                <input type="time" name="schedule_start[{{ $day }}]"
                                       value="{{ old("schedule_start.$day", $existingSchedule?->start_time ? \Carbon\Carbon::parse($existingSchedule->start_time)->format('H:i') : '') }}"
                                       class="text-sm bg-slate-50 border border-slate-300 rounded-lg px-3 py-1.5 text-slate-700
                                              focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                                <span class="text-slate-400 text-xs">to</span>
                                <input type="time" name="schedule_end[{{ $day }}]"
                                       value="{{ old("schedule_end.$day", $existingSchedule?->end_time ? \Carbon\Carbon::parse($existingSchedule->end_time)->format('H:i') : '') }}"
                                       class="text-sm bg-slate-50 border border-slate-300 rounded-lg px-3 py-1.5 text-slate-700
                                              focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-3">
                <button type="submit"
                        class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white text-sm font-semibold rounded-lg transition-colors">
                    Save Changes
                </button>
                <a href="{{ route('classes.index') }}"
                   class="px-5 py-2.5 border border-slate-300 text-slate-600 text-sm font-medium rounded-lg hover:bg-slate-50 transition-colors">
                    Cancel
                </a>
            </div>
        </form>
    </div>
@endsection
