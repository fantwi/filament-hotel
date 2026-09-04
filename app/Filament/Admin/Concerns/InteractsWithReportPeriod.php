<?php

namespace App\Filament\Admin\Concerns;

use App\Support\Reporting\ReportPeriod;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

/**
 * Provides deferred, validated period controls for custom Filament reports.
 */
trait InteractsWithReportPeriod
{
    public string $period = 'monthly';

    public string $draftPeriod = 'monthly';

    public string $startDate = '';

    public string $endDate = '';

    public string $draftStartDate = '';

    public string $draftEndDate = '';

    /**
     * Keeps applied report periods shareable and accepts dashboard drill-down dates.
     *
     * @return array<string, array<string, string>>
     */
    protected function queryStringInteractsWithReportPeriod(): array
    {
        return [
            'period' => ['except' => 'monthly'],
            'startDate' => ['except' => ''],
            'endDate' => ['except' => ''],
        ];
    }

    /**
     * Initializes applied and draft values when Livewire mounts the page.
     */
    public function mountInteractsWithReportPeriod(): void
    {
        $period = array_key_exists($this->period, ReportPeriod::options())
            ? $this->period
            : 'monthly';
        [$start, $end] = ReportPeriod::range(
            $period,
            filled($this->startDate) ? $this->startDate : null,
            filled($this->endDate) ? $this->endDate : null,
        );

        $this->setAppliedReportPeriod($period, $start, $end);
        $this->setDraftReportPeriod($period, $start, $end);
    }

    /**
     * Validates and commits the draft period in one explicit action.
     */
    public function applyReportPeriod(): void
    {
        $validated = $this->validate([
            'draftPeriod' => ['required', Rule::in(array_keys(ReportPeriod::options()))],
            'draftStartDate' => ['required', 'date', 'before_or_equal:draftEndDate'],
            'draftEndDate' => ['required', 'date', 'after_or_equal:draftStartDate'],
        ]);

        [$start, $end] = ReportPeriod::range(
            $validated['draftPeriod'],
            $validated['draftStartDate'],
            $validated['draftEndDate'],
        );

        $this->setAppliedReportPeriod($validated['draftPeriod'], $start, $end);
        $this->setDraftReportPeriod($validated['draftPeriod'], $start, $end);
        $this->resetReportPaginator();
        $this->resetValidation();
    }

    /**
     * Refreshes preset draft dates without changing the applied report range.
     */
    public function updatedDraftPeriod(string $period): void
    {
        $this->resetValidation([
            'draftPeriod',
            'draftStartDate',
            'draftEndDate',
        ]);

        if ($period === 'custom' || ! array_key_exists($period, ReportPeriod::options())) {
            return;
        }

        [$start, $end] = ReportPeriod::range($period);

        $this->draftStartDate = $start->toDateString();
        $this->draftEndDate = $end->toDateString();
    }

    /**
     * Restores and immediately applies the current monthly range.
     */
    public function resetReportPeriod(): void
    {
        [$start, $end] = ReportPeriod::range('monthly');

        $this->setAppliedReportPeriod('monthly', $start, $end);
        $this->setDraftReportPeriod('monthly', $start, $end);
        $this->resetReportPaginator();
        $this->resetValidation();
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function periodBounds(): array
    {
        return ReportPeriod::range(
            $this->period,
            filled($this->startDate) ? $this->startDate : null,
            filled($this->endDate) ? $this->endDate : null,
        );
    }

    /**
     * Returns a human-readable label for the applied period.
     */
    public function periodLabel(): string
    {
        return ReportPeriod::label(
            $this->period,
            filled($this->startDate) ? $this->startDate : null,
            filled($this->endDate) ? $this->endDate : null,
        );
    }

    /**
     * Applies the committed inclusive date range to an Eloquent query.
     */
    public function forReportPeriod(Builder $query, string $column = 'created_at'): Builder
    {
        [$start, $end] = $this->periodBounds();

        return $query->whereBetween($column, [$start, $end]);
    }

    /**
     * Override when the consuming report owns a named paginator.
     */
    protected function reportPaginatorName(): ?string
    {
        return null;
    }

    private function setAppliedReportPeriod(string $period, Carbon $start, Carbon $end): void
    {
        $this->period = $period;
        $this->startDate = $start->toDateString();
        $this->endDate = $end->toDateString();
    }

    private function setDraftReportPeriod(string $period, Carbon $start, Carbon $end): void
    {
        $this->draftPeriod = $period;
        $this->draftStartDate = $start->toDateString();
        $this->draftEndDate = $end->toDateString();
    }

    private function resetReportPaginator(): void
    {
        $paginator = $this->reportPaginatorName();

        if (filled($paginator) && method_exists($this, 'resetPage')) {
            $this->resetPage($paginator);
        }
    }
}
