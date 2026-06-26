@extends('layouts.app')

@section('title', 'Attendance')

@section('header-actions')
    <a href="{{ route('attendance.create') }}"
       class="px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary-dark transition-colors">
        + Take Attendance
    </a>
@endsection

@section('content')
    @if (session('success'))
        <div class="mb-5 px-4 py-3 bg-present-50 border border-present/30 text-present text-sm rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if ($sessions->isEmpty())
        <div class="bg-white rounded-xl border border-slate-200 p-14 text-center">
            <p class="text-slate-400 text-sm mb-3">No attendance sessions yet.</p>
            <a href="{{ route('attendance.create') }}" class="text-primary text-sm font-medium hover:underline">
                Take your first attendance →
            </a>
        </div>
    @else
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-slate-400 border-b border-slate-200 bg-slate-50">
                        <th class="text-left px-5 py-3 font-medium">Date</th>
                        <th class="text-left px-5 py-3 font-medium">Class</th>
                        <th class="text-center px-3 py-3 font-medium text-present">Present</th>
                        <th class="text-center px-3 py-3 font-medium text-late">Late</th>
                        <th class="text-center px-3 py-3 font-medium text-absent">Absent</th>
                        <th class="text-center px-3 py-3 font-medium text-excused">Excused</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($sessions as $session)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-5 py-3.5 font-medium text-slate-700">
                                {{ $session->session_date->format('M j, Y') }}
                                @if ($session->session_date->isToday())
                                    <span class="ml-1.5 text-xs font-semibold text-primary bg-primary-50 px-1.5 py-0.5 rounded">Today</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5">
                                <p class="text-slate-800 font-medium">{{ $session->classRoom->name }}</p>
                                @if ($session->classRoom->section)
                                    <p class="text-xs text-slate-400">{{ $session->classRoom->section }}</p>
                                @endif
                            </td>
                            <td class="px-3 py-3.5 text-center">
                                <span class="font-semibold text-present">{{ $session->present_count }}</span>
                            </td>
                            <td class="px-3 py-3.5 text-center">
                                <span class="font-semibold text-late">{{ $session->late_count }}</span>
                            </td>
                            <td class="px-3 py-3.5 text-center">
                                <span class="font-semibold text-absent">{{ $session->absent_count }}</span>
                            </td>
                            <td class="px-3 py-3.5 text-center">
                                <span class="font-semibold text-excused">{{ $session->excused_count }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('attendance.show', $session) }}"
                                       class="text-xs text-primary font-medium hover:underline">View / Edit</a>
                                    <form method="POST" action="{{ route('attendance.destroy', $session) }}"
                                          data-confirm
                                          data-confirm-danger
                                          data-confirm-title="Delete Attendance Session"
                                          data-confirm-message="Delete the session for &quot;{{ addslashes($session->classRoom->name) }}&quot; on {{ $session->session_date->format('M j, Y') }}? All attendance records for this session will be permanently lost."
                                          data-confirm-ok="Delete Session">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-absent hover:underline font-medium">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $sessions->links() }}
        </div>
    @endif
@endsection
