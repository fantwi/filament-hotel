@props([
    'fromLabel' => 'Start date',
    'untilLabel' => 'End date',
])

<form
    wire:submit="applyReportPeriod"
    {{ $attributes->class(['rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900']) }}
>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
            Report period
            <x-filament::input.wrapper class="mt-1">
                <x-filament::input.select wire:model.live="draftPeriod">
                    @foreach (\App\Support\Reporting\ReportPeriod::options() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
            @error('draftPeriod')
                <span class="mt-1 block text-xs text-danger-600 dark:text-danger-400">{{ $message }}</span>
            @enderror
        </label>

        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
            {{ $fromLabel }}
            <x-filament::input.wrapper class="mt-1">
                <x-filament::input
                    wire:model="draftStartDate"
                    type="date"
                    :disabled="$this->draftPeriod !== 'custom'"
                />
            </x-filament::input.wrapper>
            @error('draftStartDate')
                <span class="mt-1 block text-xs text-danger-600 dark:text-danger-400">{{ $message }}</span>
            @enderror
        </label>

        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
            {{ $untilLabel }}
            <x-filament::input.wrapper class="mt-1">
                <x-filament::input
                    wire:model="draftEndDate"
                    type="date"
                    :disabled="$this->draftPeriod !== 'custom'"
                />
            </x-filament::input.wrapper>
            @error('draftEndDate')
                <span class="mt-1 block text-xs text-danger-600 dark:text-danger-400">{{ $message }}</span>
            @enderror
        </label>

        <div class="flex flex-col justify-end gap-2 sm:flex-row xl:flex-col 2xl:flex-row">
            <x-filament::button type="submit" icon="heroicon-o-funnel" class="w-full">
                Apply
            </x-filament::button>
            <x-filament::button
                type="button"
                color="gray"
                icon="heroicon-o-arrow-path"
                wire:click="resetReportPeriod"
                class="w-full"
            >
                Reset
            </x-filament::button>
        </div>
    </div>

    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400" aria-live="polite">
        @if ($this->draftPeriod === 'custom')
            Choose the exact start and end dates for this report.
        @else
            Dates are calculated automatically for preset periods.
        @endif
    </p>

    <div class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-gray-200 pt-3 text-sm dark:border-white/10">
        <p class="text-gray-600 dark:text-gray-300">
            Applied range:
            <span class="font-semibold text-gray-950 dark:text-white">{{ $this->periodLabel() }}</span>
            <span class="text-gray-500 dark:text-gray-400">({{ $this->startDate ?: 'Not set' }} to {{ $this->endDate ?: 'Not set' }})</span>
        </p>
        <p wire:loading.delay wire:target="applyReportPeriod,resetReportPeriod" role="status" aria-live="polite" class="font-medium text-primary-600 dark:text-primary-400">
            Updating report&hellip;
        </p>
    </div>
</form>
