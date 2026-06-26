@extends('layouts.app')

@section('title', 'Import Students')

@section('content')
    <div class="max-w-lg">
        <p class="text-slate-500 text-sm mb-5">
            Importing into: <span class="font-medium text-slate-700">{{ $classRoom->name }}</span>
            @if ($classRoom->section) · {{ $classRoom->section }} @endif
        </p>

        <div class="bg-white rounded-xl border border-slate-200 p-6 mb-4">
            <h2 class="text-slate-700 font-semibold text-base mb-1">Upload File</h2>
            <p class="text-slate-400 text-xs mb-5">Accepted formats: .xlsx, .xls, .csv — max 5 MB.</p>

            <form method="POST" action="{{ route('classes.students.import.store', $classRoom) }}"
                  enctype="multipart/form-data">
                @csrf

                <div class="mb-5">
                    <label for="file" class="block text-sm font-medium text-slate-700 mb-1.5">
                        Choose File <span class="text-absent">*</span>
                    </label>
                    <input type="file" id="file" name="file" accept=".xlsx,.xls,.csv"
                           class="w-full text-sm text-slate-600 file:mr-3 file:py-2 file:px-4
                                  file:rounded-lg file:border-0 file:text-sm file:font-medium
                                  file:bg-primary-50 file:text-primary hover:file:bg-primary-100
                                  cursor-pointer @error('file') ring-1 ring-absent rounded-lg @enderror">
                    @error('file')
                        <p class="mt-1.5 text-xs text-absent">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit"
                            class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white text-sm font-semibold rounded-lg transition-colors">
                        Import Students
                    </button>
                    <a href="{{ route('classes.show', $classRoom) }}"
                       class="px-5 py-2.5 border border-slate-300 text-slate-600 text-sm font-medium rounded-lg hover:bg-slate-50 transition-colors">
                        Cancel
                    </a>
                </div>
            </form>
        </div>

        {{-- Format guide --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3 class="text-slate-700 font-semibold text-sm mb-3">Required Column Headers</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="text-slate-400 border-b border-slate-100">
                            <th class="text-left pb-2 font-medium">Column</th>
                            <th class="text-left pb-2 font-medium">Required</th>
                            <th class="text-left pb-2 font-medium">Accepted Headers</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-slate-600">
                        <tr>
                            <td class="py-2 font-medium">Student Number</td>
                            <td class="py-2 text-absent font-medium">Yes</td>
                            <td class="py-2 text-slate-400 font-mono">student_number, student_id</td>
                        </tr>
                        <tr>
                            <td class="py-2 font-medium">Full Name</td>
                            <td class="py-2 text-absent font-medium">Yes</td>
                            <td class="py-2 text-slate-400 font-mono">fullname, full_name, name</td>
                        </tr>
                        <tr>
                            <td class="py-2 font-medium">Email</td>
                            <td class="py-2 text-slate-400">Optional</td>
                            <td class="py-2 text-slate-400 font-mono">email, student_email</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="text-slate-400 text-xs mt-3">The first row must contain column headers. Existing students (matched by student number) are skipped and not duplicated.</p>
        </div>
    </div>
@endsection
