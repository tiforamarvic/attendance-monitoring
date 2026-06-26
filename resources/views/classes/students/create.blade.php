@extends('layouts.app')

@section('title', 'Add Student')

@section('content')
    <div class="max-w-lg">
        <p class="text-slate-500 text-sm mb-5">
            Adding to: <span class="font-medium text-slate-700">{{ $classRoom->name }}</span>
            @if ($classRoom->section) · {{ $classRoom->section }} @endif
        </p>

        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h2 class="text-slate-700 font-semibold text-base mb-1">Student Details</h2>
            @if ($existingStudents->isNotEmpty())
                <p class="text-slate-400 text-xs mb-5">
                    Start typing a student number or name to search existing students — fields will fill automatically.
                </p>
            @else
                <div class="mb-5"></div>
            @endif

            <form method="POST" action="{{ route('classes.students.store', $classRoom) }}" autocomplete="off">
                @csrf

                <div class="space-y-4">
                    <div class="relative">
                        <label for="student_number" class="block text-sm font-medium text-slate-700 mb-1.5">
                            Student Number <span class="text-absent">*</span>
                        </label>
                        <input type="text" id="student_number" name="student_number"
                               value="{{ old('student_number') }}"
                               autocomplete="off"
                               class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-slate-800 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary
                                      @error('student_number') border-absent @enderror"
                               placeholder="e.g. 2021-00123">
                        <div id="suggest-number"
                             class="absolute z-20 left-0 right-0 bg-white border border-slate-200 rounded-lg shadow-lg mt-1 overflow-hidden hidden">
                        </div>
                        @error('student_number')
                            <p class="mt-1.5 text-xs text-absent">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="relative">
                        <label for="fullname" class="block text-sm font-medium text-slate-700 mb-1.5">
                            Full Name <span class="text-absent">*</span>
                        </label>
                        <input type="text" id="fullname" name="fullname"
                               value="{{ old('fullname') }}"
                               autocomplete="off"
                               class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-slate-800 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary
                                      @error('fullname') border-absent @enderror"
                               placeholder="e.g. Juan Dela Cruz">
                        <div id="suggest-name"
                             class="absolute z-20 left-0 right-0 bg-white border border-slate-200 rounded-lg shadow-lg mt-1 overflow-hidden hidden">
                        </div>
                        @error('fullname')
                            <p class="mt-1.5 text-xs text-absent">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                        <input type="email" id="email" name="email"
                               value="{{ old('email') }}"
                               class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-lg text-slate-800 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary
                                      @error('email') border-absent @enderror"
                               placeholder="e.g. student@school.edu">
                        @error('email')
                            <p class="mt-1.5 text-xs text-absent">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex items-center gap-3 mt-6">
                    <button type="submit"
                            class="px-5 py-2.5 bg-primary hover:bg-primary-dark text-white text-sm font-semibold rounded-lg transition-colors">
                        Add Student
                    </button>
                    <a href="{{ route('classes.show', $classRoom) }}"
                       class="px-5 py-2.5 border border-slate-300 text-slate-600 text-sm font-medium rounded-lg hover:bg-slate-50 transition-colors">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const students = @json($existingStudents->values()->toArray());

    const numInput   = document.getElementById('student_number');
    const nameInput  = document.getElementById('fullname');
    const emailInput = document.getElementById('email');
    const suggestNum  = document.getElementById('suggest-number');
    const suggestName = document.getElementById('suggest-name');

    function fill(student) {
        numInput.value   = student.student_number;
        nameInput.value  = student.fullname;
        emailInput.value = student.email ?? '';
        closeAll();
    }

    function highlight(label, q) {
        const idx = label.toLowerCase().indexOf(q.toLowerCase());
        if (idx < 0) return label;
        return label.slice(0, idx)
            + '<span class="font-semibold text-primary">' + label.slice(idx, idx + q.length) + '</span>'
            + label.slice(idx + q.length);
    }

    function renderDropdown(container, matches, highlightKey, query) {
        if (!matches.length) { container.classList.add('hidden'); return; }

        container.innerHTML = matches.slice(0, 8).map(s => {
            const label = s[highlightKey];
            const sub   = highlightKey === 'student_number' ? s.fullname : s.student_number;
            const hl    = highlight(label, query);
            return '<button type="button" data-id="' + s.id + '" class="w-full text-left px-3 py-2.5 hover:bg-slate-50 flex items-center justify-between gap-3 border-b border-slate-50 last:border-0">'
                + '<span class="text-slate-800 text-sm">' + hl + '</span>'
                + '<span class="text-slate-400 text-xs shrink-0">' + sub + '</span>'
                + '</button>';
        }).join('');

        container.querySelectorAll('button').forEach(btn => {
            btn.addEventListener('mousedown', e => {
                e.preventDefault();
                const match = students.find(s => s.id == btn.dataset.id);
                if (match) fill(match);
            });
        });

        container.classList.remove('hidden');
    }

    function closeAll() {
        suggestNum.classList.add('hidden');
        suggestName.classList.add('hidden');
    }

    numInput.addEventListener('input', function () {
        const q = this.value.trim().toLowerCase();
        if (!q) { suggestNum.classList.add('hidden'); return; }
        const matches = students.filter(s => s.student_number.toLowerCase().includes(q));
        renderDropdown(suggestNum, matches, 'student_number', q);
    });

    nameInput.addEventListener('input', function () {
        const q = this.value.trim().toLowerCase();
        if (!q) { suggestName.classList.add('hidden'); return; }
        const matches = students.filter(s => s.fullname.toLowerCase().includes(q));
        renderDropdown(suggestName, matches, 'fullname', q);
    });

    document.addEventListener('click', e => {
        if (!numInput.contains(e.target) && !suggestNum.contains(e.target))  suggestNum.classList.add('hidden');
        if (!nameInput.contains(e.target) && !suggestName.contains(e.target)) suggestName.classList.add('hidden');
    });

    numInput.addEventListener('keydown',  e => { if (e.key === 'Escape') closeAll(); });
    nameInput.addEventListener('keydown', e => { if (e.key === 'Escape') closeAll(); });
</script>
@endpush
