<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\ConferenceBooking;
use App\Models\ConferenceFacility;
use App\Models\ConferenceRoom;
use App\Models\ContactMessage;
use App\Models\Facility;
use App\Models\Guest;
use App\Models\Ingredient;
use App\Models\KitchenProduction;
use App\Models\MenuItem;
use App\Models\Payment;
use App\Models\RecipeIngredient;
use App\Models\Restaurant;
use App\Models\RestaurantOrder;
use App\Models\RestaurantReservation;
use App\Models\RestaurantTable;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Services\KitchenStockService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

class DemoOperationalDataSeeder extends Seeder
{
    public function run(): void
    {
        $guest = $this->guest();
        [$standard, $deluxe] = $this->rooms();
        $this->conferenceRooms();
        $this->contactMessage($guest);
        $this->hotelBooking($guest, $standard);
        $conferenceBooking = $this->conferenceBooking($guest);
        [$reservation, $table] = $this->restaurantReservation($guest);
        $this->restaurantOrder($guest, $reservation, $table);
        $this->stockAndProduction();

        ActivityLog::firstOrCreate(
            ['action' => 'seeded', 'model' => 'DemoOperationalData', 'record_id' => $guest->id],
            ['user_id' => $guest->user_id, 'new_values' => ['message' => 'Demo operational data seeded.'], 'ip_address' => '127.0.0.1'],
        );

        if (! Activity::query()->exists()) {
            activity()
                ->causedBy($guest->user)
                ->performedOn($guest)
                ->event('seeded')
                ->log('Demo operational data seeded.');
        }
    }

    private function guest(): Guest
    {
        $user = User::firstOrCreate(
            ['email' => 'guest@example.com'],
            [
                'first_name' => 'Ama',
                'last_name' => 'Mensah',
                'department' => 'guest',
                'status' => User::STATUS_OFFLINE,
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ],
        );

        $user->assignRole(Role::findOrCreate('guest', 'web'));

        return Guest::updateOrCreate(
            ['user_id' => $user->id],
            ['first_name' => 'Ama', 'last_name' => 'Mensah', 'email' => $user->email, 'phone_number' => '0240000000', 'id_number' => 'GHA-DEMO-001'],
        );
    }

    private function rooms(): array
    {
        $standard = RoomType::updateOrCreate(
            ['name' => 'Standard Room'],
            ['price_per_night' => 350, 'capacity' => 2, 'description' => 'Comfortable room for business and leisure stays.', 'is_published' => true],
        );
        $deluxe = RoomType::updateOrCreate(
            ['name' => 'Deluxe Suite'],
            ['price_per_night' => 650, 'capacity' => 4, 'description' => 'Spacious suite with a separate sitting area.', 'is_published' => true],
        );

        $facilities = collect([
            ['name' => 'Air Conditioning', 'icon' => '❄️'],
            ['name' => 'Breakfast Included', 'icon' => '☕'],
            ['name' => 'Free WiFi', 'icon' => '📶'],
        ])->map(fn (array $facility) => Facility::updateOrCreate(['name' => $facility['name']], [...$facility, 'is_published' => true])->id);
        $standard->facilities()->syncWithoutDetaching($facilities);
        $deluxe->facilities()->syncWithoutDetaching($facilities);

        foreach ([['101', $standard], ['102', $standard], ['201', $deluxe], ['202', $deluxe]] as [$number, $type]) {
            Room::updateOrCreate(['room_number' => $number], ['room_type_id' => $type->id, 'status' => 'available']);
        }

        return [$standard, $deluxe];
    }

    private function conferenceRooms(): void
    {
        $facilities = collect([
            ['name' => 'Projector', 'icon' => '📽️'],
            ['name' => 'Conference WiFi', 'icon' => '📶'],
            ['name' => 'Sound System', 'icon' => '🔊'],
        ])->map(fn (array $facility) => ConferenceFacility::updateOrCreate(['name' => $facility['name']], [...$facility, 'is_published' => true])->id);

        foreach ([
            ['name' => 'Executive Boardroom', 'capacity' => 16, 'price_per_hour' => 500, 'location' => 'First Floor', 'description' => 'Private boardroom for executive meetings.'],
            ['name' => 'Grand Conference Hall', 'capacity' => 120, 'price_per_hour' => 1200, 'location' => 'Ground Floor', 'description' => 'Flexible hall for seminars and celebrations.'],
        ] as $data) {
            $room = ConferenceRoom::updateOrCreate(['name' => $data['name']], [...$data, 'is_available' => true, 'is_published' => true]);
            $room->facilities()->syncWithoutDetaching($facilities);
        }
    }

    private function contactMessage(Guest $guest): void
    {
        ContactMessage::firstOrCreate(
            ['email' => $guest->email, 'subject' => 'Corporate stay enquiry'],
            ['name' => $guest->full_name, 'phone_number' => $guest->phone_number, 'message' => 'Please share your corporate accommodation and meeting package options.'],
        );
    }

    private function hotelBooking(Guest $guest, RoomType $type): void
    {
        $room = $type->rooms()->orderBy('room_number')->firstOrFail();
        $booking = Booking::firstOrCreate(
            ['guest_id' => $guest->id, 'room_id' => $room->id, 'check_in' => today()->subDays(7), 'check_out' => today()->subDays(5)],
            ['status' => 'checked_out', 'hold_status' => 'confirmed', 'total_price' => 700, 'transaction_reference' => 'DEMO-HOTEL-001'],
        );

        Payment::firstOrCreate(
            ['transaction_reference' => 'DEMO-HOTEL-PAY-001'],
            ['booking_id' => $booking->id, 'guest_id' => $guest->id, 'amount' => 700, 'method' => 'card', 'payment_status' => 'completed'],
        );
    }

    private function conferenceBooking(Guest $guest): ConferenceBooking
    {
        $room = ConferenceRoom::where('name', 'Executive Boardroom')->firstOrFail();
        $booking = ConferenceBooking::firstOrCreate(
            ['guest_id' => $guest->id, 'conference_room_id' => $room->id, 'booking_date' => today()->subDays(3), 'start_time' => '10:00:00'],
            ['end_time' => '13:00:00', 'attendees' => 12, 'total_price' => 1500, 'status' => 'confirmed', 'payment_status' => 'paid', 'transaction_reference' => 'DEMO-CONF-001'],
        );

        Payment::firstOrCreate(
            ['transaction_reference' => 'DEMO-CONF-PAY-001'],
            ['conference_booking_id' => $booking->id, 'guest_id' => $guest->id, 'amount' => 1500, 'method' => 'card', 'payment_status' => 'completed'],
        );

        return $booking;
    }

    private function restaurantReservation(Guest $guest): array
    {
        $restaurant = Restaurant::where('name', 'My Hotel Restaurant')->firstOrFail();
        $table = RestaurantTable::where('restaurant_id', $restaurant->id)->where('table_number', 'T1')->firstOrFail();
        $reservation = RestaurantReservation::firstOrCreate(
            ['restaurant_table_id' => $table->id, 'reservation_date' => today()->subDays(2), 'reservation_time' => '19:00:00'],
            [
                'restaurant_id' => $restaurant->id,
                'guest_id' => $guest->id,
                'guest_name' => $guest->full_name,
                'guest_email' => $guest->email,
                'guest_phone' => $guest->phone_number,
                'number_of_guests' => 2,
                'reservation_fee' => 100,
                'status' => 'completed',
                'payment_status' => 'completed',
                'hold_status' => 'confirmed',
                'duration_minutes' => 120,
                'access_token' => Str::random(64),
                'transaction_reference' => 'DEMO-RES-001',
            ],
        );

        Payment::firstOrCreate(
            ['transaction_reference' => 'DEMO-RES-PAY-001'],
            ['restaurant_reservation_id' => $reservation->id, 'guest_id' => $guest->id, 'amount' => 100, 'method' => 'card', 'payment_status' => 'completed'],
        );

        return [$reservation, $table];
    }

    private function restaurantOrder(Guest $guest, RestaurantReservation $reservation, RestaurantTable $table): void
    {
        $item = MenuItem::where('name', 'Jollof Rice')->firstOrFail();
        $order = RestaurantOrder::firstOrCreate(
            ['order_number' => 'DEMO-FOOD-001'],
            [
                'guest_id' => $guest->id,
                'restaurant_reservation_id' => $reservation->id,
                'restaurant_table_id' => $table->id,
                'ordering_channel' => 'staff',
                'customer_email' => $guest->email,
                'transaction_reference' => 'DEMO-FOOD-TXN-001',
                'payment_method' => 'card',
                'paid_at' => now()->subDays(2),
                'subtotal' => 190,
                'total' => 190,
                'status' => 'served',
                'payment_status' => 'completed',
                'confirmed_at' => now()->subDays(2),
                'preparing_at' => now()->subDays(2),
                'ready_at' => now()->subDays(2),
                'served_at' => now()->subDays(2),
            ],
        );
        $order->items()->updateOrCreate(
            ['menu_item_id' => $item->id],
            ['item_name' => $item->name, 'production_unit' => 'portion', 'production_usage_per_sale' => 1, 'quantity' => 2, 'unit_price' => 95, 'total_price' => 190, 'ingredient_usage_snapshot' => []],
        );
        Payment::firstOrCreate(
            ['transaction_reference' => 'DEMO-FOOD-PAY-001'],
            ['restaurant_order_id' => $order->id, 'guest_id' => $guest->id, 'amount' => 190, 'method' => 'card', 'payment_status' => 'completed'],
        );
    }

    private function stockAndProduction(): void
    {
        $restaurant = Restaurant::where('name', 'My Hotel Restaurant')->firstOrFail();
        $stock = app(KitchenStockService::class);
        $ingredients = collect([
            ['name' => 'Rice', 'unit' => 'kg', 'quantity' => 50, 'cost' => 18, 'category' => 'Dry Goods'],
            ['name' => 'Chicken', 'unit' => 'kg', 'quantity' => 30, 'cost' => 42, 'category' => 'Meat'],
            ['name' => 'Cooking Oil', 'unit' => 'litre', 'quantity' => 20, 'cost' => 30, 'category' => 'Cooking'],
        ])->mapWithKeys(function (array $data) use ($restaurant, $stock): array {
            $ingredient = Ingredient::firstOrCreate(
                ['restaurant_id' => $restaurant->id, 'name' => $data['name']],
                ['unit' => $data['unit'], 'category' => $data['category'], 'reorder_level' => 5, 'unit_cost' => $data['cost']],
            );
            if ($ingredient->stockMovements()->doesntExist()) {
                $stock->receive($ingredient, $data['quantity'], $data['cost'], 'DEMO-OPENING-'.$ingredient->id, 'Opening demonstration stock.');
            }

            return [$data['name'] => $ingredient];
        });

        $menuItem = MenuItem::where('name', 'Jollof Rice')->firstOrFail();
        $menuItem->update(['tracks_kitchen_production' => true, 'production_unit' => 'portion', 'production_usage_per_sale' => 1, 'inventory_consumption_mode' => 'production_batch']);
        foreach (['Rice' => 0.25, 'Chicken' => 0.20, 'Cooking Oil' => 0.03] as $name => $quantity) {
            RecipeIngredient::updateOrCreate(['menu_item_id' => $menuItem->id, 'ingredient_id' => $ingredients[$name]->id], ['quantity_per_item' => $quantity]);
        }

        $production = KitchenProduction::firstOrCreate(
            ['menu_item_id' => $menuItem->id, 'production_date' => today()->subDay()],
            ['batch_reference' => 'KP-DEMO-001', 'quantity_produced' => 10, 'quantity_wasted' => 0, 'notes' => 'Demo production batch.'],
        );
        $stock->consumeForProduction($production);
    }
}
