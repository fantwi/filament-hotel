<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\Auth\EditProfile;
use App\Models\User;
use App\Providers\Filament\AdminPanelProvider;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentStaffProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_panel_registers_the_full_page_staff_profile(): void
    {
        $panel = (new AdminPanelProvider($this->app))->panel(Panel::make());

        self::assertSame(EditProfile::class, $panel->getProfilePage());
        self::assertFalse($panel->isProfilePageSimple());
    }

    public function test_authenticated_staff_can_open_the_profile_page(): void
    {
        $admin = User::factory()->create(['department' => 'admin']);

        $this->actingAs($admin)
            ->get('/admin/profile')
            ->assertOk()
            ->assertSee('My profile')
            ->assertSee(route('filament.admin.auth.profile'), false);
    }

    public function test_profile_form_uses_personal_fields_and_read_only_staff_metadata(): void
    {
        $admin = User::factory()->create(['department' => 'admin', 'status' => User::STATUS_ONLINE]);

        $this->profileComponent($admin)
            ->assertFormFieldExists('first_name')
            ->assertFormFieldExists('last_name')
            ->assertFormFieldExists('email')
            ->assertFormFieldExists('phone_number')
            ->assertFormFieldDoesNotExist('name')
            ->assertFormFieldDoesNotExist('department')
            ->assertFormFieldDoesNotExist('role')
            ->assertFormFieldDoesNotExist('status')
            ->assertSchemaComponentExists('department_display')
            ->assertSchemaComponentExists('role_display')
            ->assertSchemaComponentExists('status_display');
    }

    public function test_staff_can_update_personal_information_without_changing_access_fields(): void
    {
        $admin = User::factory()->create([
            'first_name' => 'Old',
            'last_name' => 'Name',
            'email' => 'old-admin@example.test',
            'phone_number' => '0200000000',
            'department' => 'admin',
            'status' => User::STATUS_ONLINE,
        ]);

        $this->profileComponent($admin)
            ->fillForm([
                'first_name' => 'Updated',
                'last_name' => 'Administrator',
                'email' => 'old-admin@example.test',
                'phone_number' => '0241112233',
            ])
            ->set('data.department', 'super_admin')
            ->set('data.status', User::STATUS_SUSPENDED)
            ->call('save')
            ->assertHasNoFormErrors();

        $admin->refresh();

        self::assertSame('Updated', $admin->first_name);
        self::assertSame('Administrator', $admin->last_name);
        self::assertSame('0241112233', $admin->phone_number);
        self::assertSame('admin', $admin->department);
        self::assertSame(User::STATUS_ONLINE, $admin->status);
        self::assertArrayNotHasKey('name', $admin->getAttributes());
    }

    public function test_changing_email_requires_the_current_password(): void
    {
        $admin = User::factory()->create([
            'email' => 'current-admin@example.test',
            'password' => 'current-password',
            'department' => 'admin',
        ]);

        $component = $this->profileComponent($admin)
            ->fillForm([
                'first_name' => $admin->first_name,
                'last_name' => $admin->last_name,
                'email' => 'new-admin@example.test',
                'phone_number' => $admin->phone_number,
            ])
            ->call('save')
            ->assertHasFormErrors(['currentPassword' => 'required']);

        $component
            ->fillForm([
                'first_name' => $admin->first_name,
                'last_name' => $admin->last_name,
                'email' => 'new-admin@example.test',
                'phone_number' => $admin->phone_number,
                'currentPassword' => 'incorrect-password',
            ])
            ->call('save')
            ->assertHasFormErrors(['currentPassword' => 'current_password']);

        self::assertSame('current-admin@example.test', $admin->fresh()->email);
    }

    public function test_staff_can_change_email_and_password_with_the_current_password(): void
    {
        $admin = User::factory()->create([
            'email' => 'secure-admin@example.test',
            'password' => 'current-password',
            'department' => 'admin',
        ]);

        $this->profileComponent($admin)
            ->fillForm([
                'first_name' => $admin->first_name,
                'last_name' => $admin->last_name,
                'email' => 'updated-secure-admin@example.test',
                'phone_number' => $admin->phone_number,
                'password' => 'new-secure-password',
                'passwordConfirmation' => 'new-secure-password',
                'currentPassword' => 'current-password',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $admin->refresh();

        self::assertSame('updated-secure-admin@example.test', $admin->email);
        self::assertTrue(Hash::check('new-secure-password', $admin->password));
    }

    public function test_profile_rejects_an_email_owned_by_another_user(): void
    {
        User::factory()->create(['email' => 'existing@example.test']);
        $admin = User::factory()->create([
            'email' => 'admin@example.test',
            'password' => 'current-password',
            'department' => 'admin',
        ]);

        $this->profileComponent($admin)
            ->fillForm([
                'first_name' => $admin->first_name,
                'last_name' => $admin->last_name,
                'email' => 'existing@example.test',
                'phone_number' => $admin->phone_number,
                'currentPassword' => 'current-password',
            ])
            ->call('save')
            ->assertHasFormErrors(['email' => 'unique']);

        self::assertSame('admin@example.test', $admin->fresh()->email);
    }

    private function profileComponent(User $user): Testable
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        return Livewire::actingAs($user)->test(EditProfile::class);
    }
}
