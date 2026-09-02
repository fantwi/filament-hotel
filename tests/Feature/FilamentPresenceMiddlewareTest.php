<?php

namespace Tests\Feature;

use App\Enums\StaffAccountStatus;
use App\Filament\Admin\Pages\Auth\EditProfile;
use App\Http\Middleware\UpdateLastSeen;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Livewire\Component;
use Tests\TestCase;

class FilamentPresenceMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_real_filament_profile_request_refreshes_stale_staff_presence(): void
    {
        $frozenNow = CarbonImmutable::parse('2026-09-02 12:00:00 UTC');
        $this->travelTo($frozenNow);

        $admin = User::factory()->create([
            'department' => 'admin',
            'status' => StaffAccountStatus::Active,
            'last_seen_at' => $frozenNow->subMinutes(2),
        ]);

        $this->actingAs($admin)
            ->get(route('filament.admin.auth.profile'))
            ->assertOk();

        self::assertSame(
            $frozenNow->toDateTimeString(),
            $admin->fresh()->last_seen_at?->toDateTimeString(),
        );
    }

    public function test_real_filament_livewire_hydration_refreshes_presence_through_persistent_middleware(): void
    {
        $frozenNow = CarbonImmutable::parse('2026-09-02 12:00:00 UTC');
        $this->travelTo($frozenNow);

        $admin = User::factory()->create([
            'department' => 'admin',
            'status' => StaffAccountStatus::Active,
            'last_seen_at' => $frozenNow,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('filament.admin.auth.profile'));
        $response->assertOk();

        $snapshot = $this->livewireSnapshotFor($response->getContent(), EditProfile::class);

        $admin->forceFill([
            'last_seen_at' => $frozenNow->subMinutes(2),
        ])->saveQuietly();

        $updateUri = app('livewire')->getUpdateUri();
        app('router')->getRoutes()
            ->match(Request::create($updateUri, 'POST'))
            ->withoutMiddleware(UpdateLastSeen::class);

        Filament::setCurrentPanel(null);

        $this->withHeader('X-Livewire', 'true')
            ->postJson($updateUri, [
                'components' => [[
                    'snapshot' => $snapshot,
                    'updates' => [],
                    'calls' => [[
                        'path' => '',
                        'method' => '$refresh',
                        'params' => [],
                    ]],
                ]],
            ])
            ->assertOk();

        self::assertSame(
            $frozenNow->toDateTimeString(),
            $admin->fresh()->last_seen_at?->toDateTimeString(),
        );
    }

    /**
     * @param  class-string<Component>  $componentClass
     */
    private function livewireSnapshotFor(string $html, string $componentClass): string
    {
        $componentName = app('livewire')->new($componentClass)->getName();
        $document = new \DOMDocument;
        @$document->loadHTML($html);

        foreach ($document->getElementsByTagName('*') as $element) {
            if (! $element->hasAttribute('wire:snapshot')) {
                continue;
            }

            $snapshot = htmlspecialchars_decode(
                $element->getAttribute('wire:snapshot'),
                ENT_QUOTES | ENT_SUBSTITUTE,
            );
            $decodedSnapshot = json_decode($snapshot, true, flags: JSON_THROW_ON_ERROR);

            if (data_get($decodedSnapshot, 'memo.name') === $componentName) {
                return $snapshot;
            }
        }

        self::fail("The Filament component [{$componentClass}] did not render a Livewire snapshot.");
    }
}
