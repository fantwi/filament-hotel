<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Payments\PaymentResource;
use App\Filament\Admin\Resources\Payments\Schemas\PaymentInfolist;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\RestaurantOrder;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaymentDetailsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_payment_details_schema_exposes_transaction_and_audit_information(): void
    {
        $livewire = new class extends Component implements HasSchemas
        {
            use InteractsWithSchemas;
        };
        $schema = PaymentInfolist::configure(Schema::make($livewire));

        foreach ([
            'transaction',
            'guest',
            'amount',
            'payment_status',
            'method',
            'transaction_reference',
            'created_at',
            'updated_at',
        ] as $component) {
            self::assertNotNull($schema->getComponent($component), $component);
        }
    }

    public function test_accountant_can_open_meaningful_payment_details_from_the_dashboard_action(): void
    {
        $role = Role::findOrCreate('accountant', 'web');
        $user = User::factory()->create(['department' => 'accountant']);
        $user->assignRole($role);
        $guest = Guest::query()->create([
            'first_name' => 'Akosua',
            'last_name' => 'Owusu',
            'email' => 'akosua@example.test',
            'phone_number' => '0240000000',
        ]);
        $order = RestaurantOrder::query()->create([
            'guest_id' => $guest->id,
            'order_number' => 'FOOD-DETAIL-001',
            'total' => 175.50,
            'status' => 'confirmed',
            'payment_status' => 'completed',
        ]);
        $payment = Payment::query()->create([
            'restaurant_order_id' => $order->id,
            'guest_id' => $guest->id,
            'amount' => 175.50,
            'method' => 'momo',
            'payment_status' => 'completed',
            'transaction_reference' => 'PAY-DETAIL-001',
        ]);

        $this->actingAs($user)
            ->get(PaymentResource::getUrl('view', ['record' => $payment]))
            ->assertOk()
            ->assertSee('Food order #'.$order->id)
            ->assertSee('Akosua Owusu')
            ->assertSee('akosua@example.test')
            ->assertSee('PAY-DETAIL-001');
    }
}
