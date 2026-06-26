<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'AttendEase') }} – @yield('title')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style type="text/tailwindcss">
        @theme {
            --font-sans: 'Inter', ui-sans-serif, system-ui, sans-serif;
            --color-primary: #3B5BDB;
            --color-primary-dark: #1E3A8A;
            --color-primary-50: #EDF2FF;
            --color-primary-100: #E0E7FF;
            --color-sidebar: #1E293B;
            --color-sidebar-dark: #0F172A;
            --color-present: #16A34A;
            --color-present-50: #F0FDF4;
            --color-late: #D97706;
            --color-late-50: #FFFBEB;
            --color-absent: #DC2626;
            --color-absent-50: #FEF2F2;
            --color-excused: #7C3AED;
            --color-excused-50: #F5F3FF;
        }
    </style>
</head>
<body class="min-h-screen bg-primary-50 flex items-center justify-center p-4">
    <div class="w-full max-w-[440px]">
        @yield('content')
    </div>
</body>
</html>
