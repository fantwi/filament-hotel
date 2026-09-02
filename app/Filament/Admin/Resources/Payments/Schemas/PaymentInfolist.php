<?php

namespace App\Filament\Admin\Resources\Payments\Schemas;

use App\Models\Payment;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

/**
 * Configures Filament administration for payment infolist.
 */
class PaymentInfolist
{
    /**
     * Configures configure for the Filament administration interface.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns([
                'default' => 1,
                'md' => 2,
            ])
            ->components([
                TextEntry::make('transaction')
                    ->label('Transaction')
                    ->state(fn (Payment $record): string => $record->transactionLabel())
                    ->icon('heroicon-o-receipt-percent')
                    ->weight('semibold'),

                TextEntry::make('guest')
                    ->label('Guest')
                    ->state(function (Payment $record): array {
                        $guest = $record->transactionGuest();

                        return array_values(array_filter([
                            $record->transactionGuestName(),
                            $guest?->email,
                        ]));
                    })
                    ->listWithLineBreaks()
                    ->icon('heroicon-o-user'),

                TextEntry::make('amount')
                    ->money('GHS')
                    ->icon('heroicon-o-banknotes'),

                TextEntry::make('payment_status')
                    ->label('Payment status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => str($state ?? 'unknown')->replace('_', ' ')->headline()->toString())
                    ->color(fn (?string $state): string => match ($state) {
                        'paid', 'completed' => 'success',
                        'pending', 'unpaid', 'partial', 'partially_paid' => 'warning',
                        'refunded', 'refund' => 'info',
                        'failed', 'cancelled', 'expired' => 'danger',
                        default => 'gray',
                    }),

                TextEntry::make('method')
                    ->label('Payment method')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => str($state ?? 'Not recorded')->replace('_', ' ')->headline()->toString())
                    ->color(fn (?string $state): string => match ($state) {
                        'cash' => 'success',
                        'momo', 'mobile_money', 'paystack' => 'primary',
                        'card', 'bank_transfer', 'bank' => 'warning',
                        default => 'gray',
                    })
                    ->placeholder('Not recorded'),

                TextEntry::make('transaction_reference')
                    ->label('Transaction reference')
                    ->copyable()
                    ->placeholder('Not recorded'),

                TextEntry::make('created_at')
                    ->label('Recorded at')
                    ->dateTime('M d, Y g:i A')
                    ->placeholder('Not recorded'),

                TextEntry::make('updated_at')
                    ->label('Last updated')
                    ->dateTime('M d, Y g:i A')
                    ->placeholder('Not recorded'),
            ]);
    }
}
