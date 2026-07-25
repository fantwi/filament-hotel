<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>{{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="flex min-h-screen flex-col bg-slate-50 text-gray-900">

<main class="flex flex-1 items-center justify-center px-4 py-6 sm:px-6 sm:py-10">
<div class="w-full max-w-md">

    <!-- Logo -->
    <div class="mb-6 text-center sm:mb-8">

        <a href="/"
           class="inline-flex items-center gap-2 text-2xl font-bold text-blue-600 sm:text-3xl">

            🏨 My Hotel

        </a>

    </div>

    <!-- Card -->
    <div class="rounded-2xl bg-white p-5 shadow-xl shadow-slate-200/70 ring-1 ring-slate-900/5 sm:p-8">

        {{ $slot }}

    </div>

</div>
</main>

@include('layouts.partials.public-footer')

</body>
</html>
