<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Filament\Admin\Resources\ActivityLogs\Tables\ActivityLogsTable;
use App\Models\Guest;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ActivityLogTableFiltersTest extends TestCase
{
    use RefreshDatabase;

    private ?Guest $guest = null;

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

    public function test_activity_log_description_filter_matches_each_advertised_production_event_shape(): void
    {
        $admin = $this->admin();
        $matches = [
            'created' => [$this->activity($admin, ['event' => 'created', 'description' => 'Booking created'])],
            'updated' => [$this->activity($admin, ['event' => 'updated', 'description' => 'Guest updated'])],
            'deleted' => [$this->activity($admin, ['event' => 'deleted', 'description' => 'User deleted'])],
            'checked_in' => [
                $this->activity($admin, ['event' => 'checked_in', 'description' => 'Restaurant guest checked in.']),
                $this->activity($admin, ['description' => 'Checked in guest Ada to room 101']),
            ],
            'checked_out' => [
                $this->activity($admin, ['event' => 'checked_out', 'description' => 'Restaurant guest checked out.']),
                $this->activity($admin, ['description' => 'Checked out guest Ada from room 101']),
            ],
            'payment_added' => [$this->activity($admin, ['description' => 'payment_added'])],
            'User logged in' => [$this->activity($admin, ['description' => 'User logged in'])],
            'User logged out' => [$this->activity($admin, ['description' => 'User logged out'])],
        ];

        $this->activity($admin, ['event' => 'archived', 'description' => 'Booking created']);
        $this->activity($admin, ['event' => 'archived', 'description' => 'Guest updated']);
        $this->activity($admin, ['event' => 'archived', 'description' => 'User deleted']);
        $this->activity($admin, ['description' => 'Guest checked out Ada from room 102']);
        $this->activity($admin, ['description' => 'Guest checked in Ada to room 102']);
        $this->activity($admin, ['description' => 'Payment added']);
        $this->activity($admin, ['description' => 'User logged in elsewhere']);
        $this->activity($admin, ['description' => 'User logged out elsewhere']);

        foreach ($matches as $choice => $activities) {
            self::assertSame(
                collect($activities)->pluck('id')->sort()->values()->all(),
                $this->filteredActivityIds($admin, 'description', $choice),
                "The [{$choice}] filter should return only its production-shaped activity rows.",
            );
        }
    }

    public function test_activity_log_causer_and_subject_filters_return_only_matching_morph_records(): void
    {
        $admin = $this->admin();
        $matchingCauser = $this->activity($admin);
        $sameIdNonUserCauser = $this->activity($admin, [
            'causer' => $this->guest($admin),
        ]);
        $differentSubjectType = $this->activity($admin, [
            'subject' => $this->guest($admin),
        ]);

        $table = $this->table();

        self::assertSame([$admin->id => 'Ada Lovelace'], $table->getFilter('causer_id')?->getOptions());
        self::assertSame('User', $table->getFilter('subject_type')?->getOptions()[User::class] ?? null);
        self::assertSame('Guest', $table->getFilter('subject_type')?->getOptions()[Guest::class] ?? null);
        self::assertSame([$matchingCauser->id, $differentSubjectType->id], $this->filteredActivityIds($admin, 'causer_id', $admin->id));
        self::assertSame([$matchingCauser->id, $sameIdNonUserCauser->id], $this->filteredActivityIds($admin, 'subject_type', User::class));
        self::assertSame([$differentSubjectType->id], $this->filteredActivityIds($admin, 'subject_type', Guest::class));
    }

    public function test_activity_log_date_range_is_inclusive_and_today_excludes_non_today_records(): void
    {
        $this->travelTo('2026-09-10 12:00:00');
        $admin = $this->admin();
        $fromBoundary = $this->activity($admin, ['created_at' => Carbon::parse('2026-09-01 00:00:00')]);
        $untilBoundary = $this->activity($admin, ['created_at' => Carbon::parse('2026-09-02 23:59:59')]);
        $beforeRange = $this->activity($admin, ['created_at' => Carbon::parse('2026-08-31 23:59:59')]);
        $afterRange = $this->activity($admin, ['created_at' => Carbon::parse('2026-09-03 00:00:00')]);
        $today = $this->activity($admin, ['created_at' => Carbon::parse('2026-09-10 23:59:59')]);
        $notToday = $this->activity($admin, ['created_at' => Carbon::parse('2026-09-09 23:59:59')]);

        self::assertSame(
            [$fromBoundary->id, $untilBoundary->id],
            $this->filteredActivityIds($admin, 'created_at', [
                'from' => '2026-09-01',
                'until' => '2026-09-02',
            ]),
        );
        self::assertSame([$today->id], $this->filteredActivityIds($admin, 'today'));

        $this->travelBack();
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

    private function admin(): User
    {
        $admin = User::factory()->create([
            'department' => 'admin',
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
        ]);

        Activity::query()->delete();

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        return $admin;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function activity(User $admin, array $attributes = []): Activity
    {
        $causer = $attributes['causer'] ?? $admin;
        $subject = $attributes['subject'] ?? $admin;
        $activity = activity()
            ->causedBy($causer)
            ->performedOn($subject);

        if (filled($attributes['event'] ?? null)) {
            $activity->event($attributes['event']);
        }

        if (isset($attributes['created_at'])) {
            $activity->createdAt($attributes['created_at']);
        }

        $logged = $activity->log($attributes['description'] ?? 'Unrelated activity');

        Activity::query()
            ->whereKey($logged->getKey())
            ->update([
                'causer_type' => $causer->getMorphClass(),
                'causer_id' => $causer->getKey(),
            ]);

        return $logged->refresh();
    }

    private function guest(User $admin): Guest
    {
        return $this->guest ??= Guest::withoutEvents(fn (): Guest => Guest::query()->create([
            'id' => $admin->id,
            'first_name' => 'Guest',
            'last_name' => 'Actor',
            'email' => 'guest-actor@example.test',
        ]));
    }

    /**
     * @param  array<string, mixed>|string|null  $data
     * @return list<int>
     */
    private function filteredActivityIds(User $admin, string $filter, array|string|null $data = null): array
    {
        $component = Livewire::actingAs($admin)
            ->test(ListActivityLogs::class)
            ->filterTable($filter, $data);

        $query = $component->instance()->getFilteredTableQuery();

        return $query
            ->pluck('id')
            ->sort()
            ->values()
            ->all();
    }
}
