<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-w-0 overflow-x-hidden bg-gray-100 font-sans text-gray-800 antialiased">

    <!-- NAVBAR -->
    @include('layouts.partials.guest-nav')

    <!-- PAGE CONTENT -->
    <main class="min-w-0 pt-16 sm:pt-20">
        {{ $slot }}
    </main>

</body>
</html>
