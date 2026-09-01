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
use App\Models\User;
use App\Providers\Filament\AdminPanelProvider;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class FilamentNavigationGroupingTest extends TestCase
{
    use RefreshDatabase;

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
        ], array_map(
            fn (NavigationGroup|string $group): string => $group instanceof NavigationGroup
                ? (string) $group->getLabel()
                : $group,
            $panel->getNavigationGroups(),
        ));
    }

    public function test_admin_navigation_groups_define_their_initial_collapsed_state(): void
    {
        $panel = (new AdminPanelProvider($this->app))->panel(Panel::make());
        $groups = $panel->getNavigationGroups();

        foreach ($groups as $group) {
            if (! $group instanceof NavigationGroup) {
                self::fail('Navigation groups must be registered as NavigationGroup objects.');
            }
        }

        /** @var array<string, NavigationGroup> $groupsByLabel */
        $groupsByLabel = collect($groups)->keyBy(fn (NavigationGroup $group): string => (string) $group->getLabel())->all();

        self::assertFalse($groupsByLabel['Dashboards']->isCollapsed());
        self::assertFalse($groupsByLabel['Accommodation']->isCollapsed());

        foreach ([
            'Conferences',
            'Restaurant Sales',
            'Kitchen & Inventory',
            'Guests & Communications',
            'Finance',
            'Reports',
            'Access & Administration',
            'Hotel Configuration',
        ] as $label) {
            self::assertTrue($groupsByLabel[$label]->isCollapsible(), $label);
            self::assertTrue($groupsByLabel[$label]->isCollapsed(), $label);
        }
    }

    public function test_all_navigation_groups_remain_collapsible_after_items_are_mounted(): void
    {
        $panel = (new AdminPanelProvider($this->app))->panel(Panel::make());
        $groups = $panel->getNavigationGroups();

        foreach ($groups as $index => $group) {
            self::assertInstanceOf(NavigationGroup::class, $group);
            $group->items([
                NavigationItem::make("Navigation item {$index}")->url("/navigation-item-{$index}")->isActiveWhen(fn (): bool => false),
            ]);
            self::assertTrue($group->isCollapsible(), $group->getLabel());
        }

        $activeSecondaryGroup = $groups[2];
        $activeSecondaryGroup->items([
            NavigationItem::make('Active conference')->url('/active-conference')->isActiveWhen(fn (): bool => true),
        ]);

        self::assertTrue($activeSecondaryGroup->isActive());
        self::assertFalse($activeSecondaryGroup->isCollapsed());
        self::assertTrue($activeSecondaryGroup->isCollapsible());

        $inactiveSecondaryGroup = $groups[3];
        $inactiveSecondaryGroup->items([
            NavigationItem::make('Inactive sale')->url('/inactive-sale')->isActiveWhen(fn (): bool => false),
        ]);

        self::assertFalse($inactiveSecondaryGroup->isActive());
        self::assertTrue($inactiveSecondaryGroup->isCollapsed());
        self::assertTrue($inactiveSecondaryGroup->isCollapsible());
    }

    public function test_rendered_sidebar_initializer_executes_before_initially_showing_the_active_secondary_group(): void
    {
        $admin = User::factory()->create(['department' => 'admin']);

        $response = $this->actingAs($admin)->get(ActivityLogResource::getUrl('index'));

        $response->assertOk();

        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $initializer = collect(iterator_to_array($document->getElementsByTagName('script')))
            ->map(fn (\DOMElement $script): string => $script->textContent)
            ->first(fn (string $script): bool => str_contains($script, 'const activeLabels'));

        self::assertIsString($initializer);
        self::assertStringContainsString('Activity Logs', $response->getContent());

        $process = $this->runJavaScript(<<<JS
const values = new Map([
    ['collapsedGroups', JSON.stringify(['Access & Administration', 'Reports'])],
]);
globalThis.localStorage = {
    getItem: (key) => values.has(key) ? values.get(key) : null,
    setItem: (key, value) => values.set(key, String(value)),
};
globalThis.window = {};

{$initializer}

const collapsedGroups = JSON.parse(localStorage.getItem('collapsedGroups'));

if (collapsedGroups.includes('Access & Administration') || ! collapsedGroups.includes('Reports')) {
    throw new Error('The active secondary group was not made visible before Filament initialized.');
}

if (! window.__filamentAdminNavigationActiveLabels.includes('Access & Administration')) {
    throw new Error('The rendered initializer did not publish the active secondary group.');
}
JS);

        self::assertSame(0, $process->getExitCode(), $process->getErrorOutput());
    }

    public function test_resource_navigation_icons_are_domain_specific_and_unique(): void
    {
        $resources = [
            BookingResource::class,
            ConferenceRoomResource::class,
            ConferenceFacilityResource::class,
            ContactMessageResource::class,
            FacilityResource::class,
            GuestResource::class,
            HotelSettingResource::class,
            PaymentResource::class,
            RestaurantReservationResource::class,
            RestaurantTableResource::class,
            RestaurantResource::class,
            RoomTypeResource::class,
            RoomResource::class,
            UserResource::class,
        ];

        $icons = array_map(
            fn (string $resource): string => ($icon = $resource::getNavigationIcon()) instanceof Heroicon
                ? $icon->value
                : (string) $icon,
            $resources,
        );

        self::assertCount(count($resources), array_unique($icons));
        self::assertNotContains(Heroicon::OutlinedRectangleStack->value, $icons);
    }

    private function runJavaScript(string $script): Process
    {
        $node = (new ExecutableFinder)->find('node');

        if ($node === null && is_executable('/mnt/c/Program Files/nodejs/node.exe')) {
            $node = '/mnt/c/Program Files/nodejs/node.exe';
        }

        self::assertNotNull($node, 'Node.js is required to execute rendered Filament JavaScript regressions.');

        $process = new Process([$node, '--input-type=commonjs']);
        $process->setInput($script);
        $process->run();

        return $process;
    }
}
