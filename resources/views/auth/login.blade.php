@extends('layouts.auth')

@section('title', 'Sign In')

@section('content')
<div class="bg-white rounded-2xl shadow-xl px-10 py-12">

    {{-- Logo & branding --}}
    <div class="flex flex-col items-center text-center mb-8">
        <div class="w-14 h-14 bg-primary rounded-[14px] flex items-center justify-center mb-3">
            <span class="text-white font-bold text-2xl select-none">A</span>
        </div>
        <h1 class="text-slate-800 font-bold text-2xl">AttendEase</h1>
        <p class="text-slate-400 text-sm mt-1">Manage your class attendance with ease.</p>
    </div>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        {{-- Email --}}
        <div class="mb-5">
            <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">
                Email Address
            </label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                placeholder="Enter your email address"
                class="w-full bg-slate-50 border rounded-lg px-4 py-3 text-sm text-slate-800
                       placeholder-slate-400 outline-none transition-colors
                       focus:ring-2 focus:ring-primary/30 focus:border-primary
                       {{ $errors->has('email') ? 'border-red-400' : 'border-slate-300' }}"
            >
            @error('email')
                <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        {{-- Password --}}
        <div class="mb-7">
            <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">
                Password
            </label>
            <input
                id="password"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                placeholder="Enter your password"
                class="w-full bg-slate-50 border rounded-lg px-4 py-3 text-sm text-slate-800
                       placeholder-slate-400 outline-none transition-colors
                       focus:ring-2 focus:ring-primary/30 focus:border-primary
                       {{ $errors->has('password') ? 'border-red-400' : 'border-slate-300' }}"
            >
            @error('password')
                <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        {{-- Submit --}}
        <button
            type="submit"
            class="w-full bg-primary hover:bg-primary-dark text-white font-semibold text-[15px]
                   py-3 rounded-lg transition-colors duration-150 cursor-pointer"
        >
            Sign In
        </button>
    </form>

    <div class="text-center mt-5">
        <a href="#" class="text-primary text-sm font-medium hover:underline">
            Forgot your password?
        </a>
    </div>
</div>
@endsection
