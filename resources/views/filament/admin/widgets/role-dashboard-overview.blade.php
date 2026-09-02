<x-filament-widgets::widget>
    <x-filament::section>
        <div @class([
            'flex flex-col',
            'gap-4 xl:flex-row xl:items-center xl:justify-between' => $compact,
            'gap-5 lg:flex-row lg:items-end lg:justify-between' => ! $compact,
        ])>
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">{{ $dashboard['eyebrow'] }}</p>
                <h2 @class([
                    'mt-1 font-bold tracking-tight',
                    'text-xl' => $compact,
                    'text-2xl' => ! $compact,
                ])>{{ $dashboard['title'] }}</h2>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $dashboard['description'] }}</p>
            </div>

            @unless ($compact)
                <div class="shrink-0 rounded-xl bg-gray-100 px-4 py-3 dark:bg-white/10">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Reporting period</p>
                    <p class="mt-1 text-sm font-bold text-gray-900 dark:text-white">{{ $periodLabel }}</p>
                </div>
            @endunless
        </div>

        <div @class([
            'grid md:grid-cols-3',
            'mt-4 gap-2' => $compact,
            'mt-6 gap-3' => ! $compact,
        ])>
            @foreach ($dashboard['priorities'] as $priority)
                <div @class([
                    'rounded-xl bg-gray-50 dark:bg-white/5',
                    'px-3 py-2.5' => $compact,
                    'p-4' => ! $compact,
                ])>
                    <p class="font-semibold text-gray-900 dark:text-white">{{ $priority['title'] }}</p>
                    <p @class([
                        'mt-1 text-sm text-gray-600 dark:text-gray-300',
                        'leading-5' => $compact,
                        'leading-6' => ! $compact,
                    ])>{{ $priority['description'] }}</p>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
