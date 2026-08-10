<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">{{ $dashboard['eyebrow'] }}</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight">{{ $dashboard['title'] }}</h2>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $dashboard['description'] }}</p>
            </div>
            <div class="shrink-0 rounded-xl bg-gray-100 px-4 py-3 dark:bg-white/10">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Reporting period</p>
                <p class="mt-1 text-sm font-bold text-gray-900 dark:text-white">{{ $periodLabel }}</p>
            </div>
        </div>

        <div class="mt-6 grid gap-3 md:grid-cols-3">
            @foreach ($dashboard['priorities'] as $priority)
                <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                    <p class="font-semibold text-gray-900 dark:text-white">{{ $priority['title'] }}</p>
                    <p class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $priority['description'] }}</p>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
