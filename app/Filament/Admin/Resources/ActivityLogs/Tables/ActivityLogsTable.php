<?php

namespace App\Filament\Admin\Resources\ActivityLogs\Tables;

use App\Filament\Exports\ActivityLogExporter;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ExportAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

/**
 * Configures Filament administration for activity logs table.
 */
class ActivityLogsTable
{
    /**
     * Configures configure for the Filament administration interface.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No activity logs found')
            ->emptyStateDescription('Audit events will appear here as users sign in and perform actions. Reset filters to review the full history.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            ->emptyStateActions([
                Action::make('resetFilters')
                    ->label('Reset filters')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->action(function ($livewire): void {
                        $livewire->resetTableFiltersForm();
                    }),
            ])
            ->columns([
                TextColumn::make('id')
                    ->label('Log ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('causer.first_name')
                    ->label('User')
                    ->formatStateUsing(
                        fn ($state, $record) => $record
                            ->causer?->name
                            ?? $state
                            ?? $record
                                ->subject?->name
                            ?? 'System'
                    )
                    ->searchable(['first_name', 'last_name']),

                TextColumn::make('description')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        'checked_in' => 'primary',
                        'checked_out' => 'gray',
                        'payment_added' => 'success',
                        'User logged in' => 'success',
                        'User logged out' => 'danger',
                        default => 'secondary',
                    }),

                TextColumn::make('subject_type')
                    ->label('Subject type')
                    ->formatStateUsing(fn (?string $state): string => class_basename((string) $state))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Date & Time')
                    // ->since()
                    ->dateTime('M d, Y H:i')
                    ->sortable(),

                TextColumn::make('properties.ip_address')
                    ->label('IP address')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                SelectFilter::make('description')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                        'checked_in' => 'Checked in',
                        'checked_out' => 'Checked out',
                        'payment_added' => 'Payment added',
                        'User logged in' => 'User logged in',
                        'User logged out' => 'User logged out',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'created', 'updated', 'deleted' => $query->where('event', $data['value']),
                            'checked_in' => $query->where(fn (Builder $query): Builder => $query
                                ->where('event', 'checked_in')
                                ->orWhere('description', 'like', 'Checked in guest %')),
                            'checked_out' => $query->where(fn (Builder $query): Builder => $query
                                ->where('event', 'checked_out')
                                ->orWhere('description', 'like', 'Checked out guest %')),
                            'payment_added', 'User logged in', 'User logged out' => $query
                                ->where('description', $data['value']),
                            default => $query,
                        };
                    }),

                SelectFilter::make('causer_id')
                    ->label('Acting user')
                    ->options(fn (): array => User::query()
                        ->orderBy('first_name')
                        ->orderBy('last_name')
                        ->get()
                        ->mapWithKeys(fn (User $user): array => [$user->getKey() => $user->name])
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, int|string $causerId): Builder => $query
                            ->where('causer_type', User::class)
                            ->where('causer_id', $causerId),
                    )),

                SelectFilter::make('subject_type')
                    ->label('Subject type')
                    ->options(fn (): array => Activity::query()
                        ->whereNotNull('subject_type')
                        ->distinct()
                        ->orderBy('subject_type')
                        ->pluck('subject_type', 'subject_type')
                        ->map(fn (string $subjectType): string => class_basename($subjectType))
                        ->all()),

                Filter::make('created_at')
                    ->label('Date range')
                    ->schema([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['from'] ?? null,
                            fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date),
                        )
                        ->when(
                            $data['until'] ?? null,
                            fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date),
                        )),

                Filter::make('today')
                    ->query(fn (Builder $query): Builder => $query->whereDate('created_at', today())),
            ])

            ->recordActions([
                ViewAction::make(),
            ])

            ->toolbarActions([
                ExportAction::make()
                    ->exporter(ActivityLogExporter::class)
                    ->fileName(fn () => 'activity_logs_'.now()->format('Y-m-d_H-i-s')
                    ),
            ]);
    }
}
