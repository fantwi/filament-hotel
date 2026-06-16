<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>{{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-gray-100 flex items-center justify-center px-4">

<div class="w-full max-w-md">

    <!-- Logo -->
    <div class="text-center mb-8">

        <a href="/"
           class="text-3xl font-bold text-blue-600">

            🏨 My Hotel

        </a>

    </div>

    <!-- Card -->
    <div class="bg-white shadow-xl rounded-2xl p-8">

        {{ $slot }}

    </div>

</div>

</body>
</html>