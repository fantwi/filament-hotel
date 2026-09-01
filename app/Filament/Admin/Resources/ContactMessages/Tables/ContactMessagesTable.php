<?php

namespace App\Filament\Admin\Resources\ContactMessages\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Configures Filament administration for contact messages table.
 */
class ContactMessagesTable
{
    /**
     * Configures configure for the Filament administration interface.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No contact messages found')
            ->emptyStateDescription('Messages submitted through the public contact page will appear here. Reset filters to show every message.')
            ->emptyStateIcon('heroicon-o-envelope')
            ->emptyStateActions([
                Action::make('resetFilters')
                    ->label('Reset filters')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->action(function ($livewire): void {
                        $livewire->resetTableFilters();
                    }),
            ])
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone_number')
                    ->label('Phone')
                    ->placeholder('Not provided')
                    ->toggleable(),
                TextColumn::make('subject')
                    ->label('Subject')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('message')
                    ->label('Message')
                    ->limit(100)
                    ->tooltip(fn (TextColumn $column): ?string => $column->getState())
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'new',
                        'success' => 'resolved',
                    ])
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime('M d, Y g:i A')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([SelectFilter::make('status')->options([
                'new' => 'New',
                'resolved' => 'Resolved',
            ])])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
