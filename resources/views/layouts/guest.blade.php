<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-gray-100 text-gray-800">

    <!-- NAVBAR -->
    @include('layouts.partials.guest-nav')

    <!-- PAGE CONTENT -->
    <main class="pt-20">
        {{ $slot }}
    </main>

</body>
</html>