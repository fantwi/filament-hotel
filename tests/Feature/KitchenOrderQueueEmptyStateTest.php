<?php

namespace Tests\Feature;

use App\Filament\Admin\Widgets\KitchenOrderQueue;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Tests\TestCase;

class KitchenOrderQueueEmptyStateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_live_queue_has_specific_operational_empty_state_guidance(): void
    {
        $table = $this->table();

        self::assertSame('No active kitchen orders', $table->getEmptyStateHeading());
        self::assertSame(
            'Only eligible orders in Confirmed, Preparing, or Ready status appear here. The queue refreshes automatically every 10 seconds.',
            $table->getEmptyStateDescription(),
        );
        self::assertSame('heroicon-o-check-circle', $table->getEmptyStateIcon());
        self::assertStringNotContainsString('period', strtolower((string) $table->getEmptyStateDescription()));
    }

    public function test_live_queue_empty_state_provides_a_working_refresh_action(): void
    {
        $actions = collect($this->table()->getEmptyStateActions())
            ->filter(fn (Action $action): bool => $action->isVisible())
            ->values();

        self::assertCount(1, $actions);
        self::assertContainsOnlyInstancesOf(Action::class, $actions);
        self::assertSame('refreshQueue', $actions[0]->getName());
        self::assertSame('Refresh queue', $actions[0]->getLabel());
        self::assertSame('heroicon-o-arrow-path', $actions[0]->getIcon());
        self::assertTrue($actions[0]->hasAction());
        self::assertNotNull($actions[0]->getActionFunction());
    }

    private function table(): Table
    {
        $widget = new KitchenOrderQueue;

        return $widget->table(Table::make($this->createMock(HasTable::class)));
    }
}
