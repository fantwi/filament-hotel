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
            <select wire:model="draftPeriod" class="fi-input mt-1 w-full">
                @foreach (\App\Support\Reporting\ReportPeriod::options() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            @error('draftPeriod')
                <span class="mt-1 block text-xs text-danger-600 dark:text-danger-400">{{ $message }}</span>
            @enderror
        </label>

        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
            {{ $fromLabel }}
            <input wire:model="draftStartDate" type="date" class="fi-input mt-1 w-full">
            @error('draftStartDate')
                <span class="mt-1 block text-xs text-danger-600 dark:text-danger-400">{{ $message }}</span>
            @enderror
        </label>

        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
            {{ $untilLabel }}
            <input wire:model="draftEndDate" type="date" class="fi-input mt-1 w-full">
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
