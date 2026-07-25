<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ config('app.name') }}</title>
    <script>if (localStorage.getItem('guest-theme') === 'dark' || (!localStorage.getItem('guest-theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) document.documentElement.classList.add('dark');</script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-w-0 overflow-x-hidden bg-gray-100 font-sans text-gray-800 antialiased transition-colors dark:bg-[#161b48e6] dark:text-slate-100">

    <!-- NAVBAR -->
    @include('layouts.partials.guest-nav')

    <!-- PAGE CONTENT -->
    <main class="min-w-0 pt-16 sm:pt-20">
        {{ $slot }}
    </main>

    @include('layouts.partials.public-footer')

</body>
</html>
