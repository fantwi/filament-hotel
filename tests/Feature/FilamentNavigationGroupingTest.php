<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\BookingCalendar;
use App\Filament\Admin\Pages\KitchenProductionReport;
use App\Filament\Admin\Resources\ActivityLogs\ActivityLogResource;
use App\Filament\Admin\Resources\BillingSettings\BillingSettingResource;
use App\Filament\Admin\Resources\Bookings\BookingResource;
use App\Filament\Admin\Resources\ConferenceFacilities\ConferenceFacilityResource;
use App\Filament\Admin\Resources\ConferenceRooms\ConferenceRoomResource;
use App\Filament\Admin\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Admin\Resources\CorporateOrganizations\CorporateOrganizationResource;
use App\Filament\Admin\Resources\Facilities\FacilityResource;
use App\Filament\Admin\Resources\Guests\GuestResource;
use App\Filament\Admin\Resources\HotelSettings\HotelSettingResource;
use App\Filament\Admin\Resources\Ingredients\IngredientResource;
use App\Filament\Admin\Resources\KitchenProductions\KitchenProductionResource;
use App\Filament\Admin\Resources\KitchenStockMovements\KitchenStockMovementResource;
use App\Filament\Admin\Resources\MenuCategories\MenuCategoryResource;
use App\Filament\Admin\Resources\MenuItems\MenuItemResource;
use App\Filament\Admin\Resources\Payments\PaymentResource;
use App\Filament\Admin\Resources\Promotions\PromotionResource;
use App\Filament\Admin\Resources\RestaurantOrderItems\RestaurantOrderItemResource;
use App\Filament\Admin\Resources\RestaurantOrders\RestaurantOrderResource;
use App\Filament\Admin\Resources\RestaurantReservations\RestaurantReservationResource;
use App\Filament\Admin\Resources\Restaurants\RestaurantResource;
use App\Filament\Admin\Resources\RestaurantTables\RestaurantTableResource;
use App\Filament\Admin\Resources\Rooms\RoomResource;
use App\Filament\Admin\Resources\RoomTypes\RoomTypeResource;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Providers\Filament\AdminPanelProvider;
use Filament\Panel;
use Tests\TestCase;

class FilamentNavigationGroupingTest extends TestCase
{
    public function test_admin_navigation_uses_domain_groups_for_pages_and_resources(): void
    {
        $groups = [
            BookingCalendar::class => 'Accommodation',
            BookingResource::class => 'Accommodation',
            RoomTypeResource::class => 'Accommodation',
            RoomResource::class => 'Accommodation',
            FacilityResource::class => 'Accommodation',
            ConferenceRoomResource::class => 'Conferences',
            ConferenceFacilityResource::class => 'Conferences',
            RestaurantReservationResource::class => 'Restaurant Sales',
            RestaurantTableResource::class => 'Restaurant Sales',
            RestaurantOrderResource::class => 'Restaurant Sales',
            RestaurantOrderItemResource::class => 'Restaurant Sales',
            MenuCategoryResource::class => 'Restaurant Sales',
            MenuItemResource::class => 'Restaurant Sales',
            IngredientResource::class => 'Kitchen & Inventory',
            KitchenStockMovementResource::class => 'Kitchen & Inventory',
            KitchenProductionResource::class => 'Kitchen & Inventory',
            KitchenProductionReport::class => 'Kitchen & Inventory',
            GuestResource::class => 'Guests & Communications',
            ContactMessageResource::class => 'Guests & Communications',
            PaymentResource::class => 'Finance',
            CorporateOrganizationResource::class => 'Finance',
            BillingSettingResource::class => 'Finance',
            PromotionResource::class => 'Finance',
            ActivityLogResource::class => 'Access & Administration',
            UserResource::class => 'Access & Administration',
            HotelSettingResource::class => 'Hotel Configuration',
            RestaurantResource::class => 'Hotel Configuration',
        ];

        foreach ($groups as $navigationClass => $group) {
            self::assertSame($group, $navigationClass::getNavigationGroup(), $navigationClass);
        }
    }

    public function test_admin_panel_registers_navigation_groups_in_operational_order(): void
    {
        $panel = (new AdminPanelProvider($this->app))->panel(Panel::make());

        self::assertSame([
            'Dashboards',
            'Accommodation',
            'Conferences',
            'Restaurant Sales',
            'Kitchen & Inventory',
            'Guests & Communications',
            'Finance',
            'Reports',
            'Access & Administration',
            'Hotel Configuration',
        ], $panel->getNavigationGroups());
    }
}
