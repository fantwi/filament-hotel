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

@include('layouts.partials.guest-nav')

<main class="flex flex-1 items-center justify-center px-4 py-10 pt-24 sm:px-6 sm:py-14 sm:pt-28">
<div class="w-full max-w-md">

    <!-- Card -->
    <div class="rounded-2xl bg-white p-5 shadow-xl shadow-slate-200/70 ring-1 ring-slate-900/5 sm:p-8">

        {{ $slot }}

    </div>

</div>
</main>

@include('layouts.partials.public-footer')

</body>
</html>
