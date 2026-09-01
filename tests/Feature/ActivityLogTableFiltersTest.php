<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\ActivityLogs\Tables\ActivityLogsTable;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ActivityLogTableFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_log_table_exposes_all_operational_filters_and_event_values(): void
    {
        $table = $this->table();
        $filters = $table->getFilters();

        self::assertSame([
            'description',
            'causer_id',
            'subject_type',
            'created_at',
            'today',
        ], array_keys($filters));
        self::assertInstanceOf(SelectFilter::class, $filters['description']);
        self::assertInstanceOf(SelectFilter::class, $filters['causer_id']);
        self::assertInstanceOf(SelectFilter::class, $filters['subject_type']);
        self::assertInstanceOf(Filter::class, $filters['created_at']);
        self::assertInstanceOf(Filter::class, $filters['today']);
        self::assertSame([
            'created' => 'Created',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
            'checked_in' => 'Checked in',
            'checked_out' => 'Checked out',
            'payment_added' => 'Payment added',
            'User logged in' => 'User logged in',
            'User logged out' => 'User logged out',
        ], $filters['description']->getOptions());

        $dateFields = $filters['created_at']->getSchemaComponents();

        self::assertCount(2, $dateFields);
        self::assertContainsOnlyInstancesOf(DatePicker::class, $dateFields);
        self::assertSame(['from', 'until'], array_map(
            fn (DatePicker $field): string => $field->getName(),
            $dateFields,
        ));
    }

    public function test_activity_log_filters_use_portable_causer_subject_and_date_queries(): void
    {
        $actor = User::factory()->create([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
        ]);
        $inRangeActivity = Activity::query()->create([
            'log_name' => 'default',
            'description' => 'created',
            'event' => 'created',
            'subject_type' => User::class,
            'subject_id' => $actor->id,
            'causer_type' => User::class,
            'causer_id' => $actor->id,
            'properties' => [],
            'created_at' => Carbon::parse('2020-01-02 12:00:00'),
            'updated_at' => Carbon::parse('2020-01-02 12:00:00'),
        ]);
        $table = $this->table();

        self::assertSame([$actor->id => 'Ada Lovelace'], $table->getFilter('causer_id')?->getOptions());
        self::assertSame('User', $table->getFilter('subject_type')?->getOptions()[User::class] ?? null);

        $causerQuery = $table->getFilter('causer_id')?->apply(Activity::query(), ['value' => $actor->id]);
        self::assertNotNull($causerQuery);
        self::assertSame([User::class, $actor->id], $causerQuery->getBindings());

        $subjectQuery = $table->getFilter('subject_type')?->apply(Activity::query(), ['value' => User::class]);
        self::assertNotNull($subjectQuery);
        self::assertSame([User::class], $subjectQuery->getBindings());

        $dateQuery = $table->getFilter('created_at')?->apply(Activity::query(), [
            'from' => '2020-01-02',
            'until' => '2020-01-02',
        ]);
        self::assertNotNull($dateQuery);
        self::assertSame([$inRangeActivity->id], $dateQuery->pluck('id')->all());
    }

    public function test_activity_log_secondary_columns_are_available_without_cluttering_the_default_view(): void
    {
        $table = $this->table();

        foreach (['id', 'subject_type', 'properties.ip_address'] as $columnName) {
            $column = $table->getColumn($columnName);

            self::assertNotNull($column, "Missing [{$columnName}] activity log column.");
            self::assertTrue($column->isToggleable(), "[{$columnName}] should be toggleable.");
            self::assertTrue($column->isToggledHiddenByDefault(), "[{$columnName}] should be hidden by default.");
        }
    }

    private function table(): Table
    {
        return ActivityLogsTable::configure(Table::make($this->createMock(HasTable::class)));
    }
}
