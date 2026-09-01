<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    use HasRoles;
    use LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'department',
        'corporate_organization_id',
        'phone_number',
        'id_number',
        'password',
        // 'role',
        'status',
        'profile_photo',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected static function booted()
    {
        // static::created(function ($user) {

        //     // $role = self::DEPARTMENT_ROLE_MAP[$user->department] ?? 'guest';

        //     // $user->syncRoles([$role]);

        //     // // Only assign role if explicitly provided
        //     // if (request()->has('role')) {
        //     //     $user->assignRole(request('role'));
        //     // }

        //     if ($user->department === 'guest' && !$user->guest()->exists()) {
        //         Guest::create([
        //             'user_id' => $user->id,
        //             'first_name' => $user->first_name ?? 'Guest',
        //             'last_name' => $user->last_name ?? '',
        //             'email' => $user->email,
        //             'phone_number' => $user->phone_number ?? null,
        //             'id_number' => $user->id_number ?? null,
        //         ]);
        //     }

        //     if ($user->department === 'reception') {
        //         $user->assignRole('receptionist');
        //     }

        //     if ($user->department === 'accounting') {
        //         $user->assignRole('accountant');
        //     }

        //     if ($user->department === 'admin') {
        //         $user->assignRole('admin');
        //     }

        //     if ($user->department === 'super_admin') {
        //         $user->assignRole('super_admin');
        //     }

        //     if ($user->department === 'management') {
        //         $user->assignRole('manager');
        //     }

        //     if ($user->department === 'housekeeping') {
        //         $user->assignRole('housekeeping');
        //     }

        //     if ($user->department === 'guest') {
        //         // $user->assignRole('guest');
        //         $user->assignRole('guest');
        //     }

        //     // if ($user->department === 'finance') {
        //     //     $user->assignRole('accountant');
        //     // }
        // });

        static::created(function ($user) {

            $role =
                self::DEPARTMENT_ROLE_MAP[
                    $user->department
                ] ?? 'guest';

            Role::findOrCreate($role, 'web');

            $user->syncRoles([
                $role,
            ]);

            activity()
                ->causedBy(
                    auth()->user() ?? $user
                )
                ->performedOn($user)
                ->log('User created');

            if (
                $user->department ===
                'guest'
                &&
                ! $user->guest()->exists()
            ) {

                $guest = Guest::create([

                    'user_id' => $user->id,

                    'first_name' => $user->first_name
                        ?? 'Guest',

                    'last_name' => $user->last_name
                        ?? '',

                    'email' => $user->email,

                    'phone_number' => $user->phone_number,

                    'id_number' => $user->id_number,

                ]);

                activity()
                    ->causedBy(auth()->user() ?? $user)
                    ->performedOn($guest)
                    ->log('Guest created');
            }
        });

        // static::updated(function ($user) {

        //     if ($user->isDirty('department')) {

        //         // $role = self::DEPARTMENT_ROLE_MAP[$user->department] ?? 'guest';

        //         $user->syncRoles([$role]);
        //     }

        // });

        static::updated(function ($user) {

            if (
                $user->isDirty(
                    'department'
                )
            ) {

                $role =
                    self::DEPARTMENT_ROLE_MAP[
                        $user->department
                    ] ?? 'guest';

                Role::findOrCreate($role, 'web');

                $user->syncRoles([
                    $role,
                ]);
            }
        });

        // static::saved(function ($user) {
        //     if ($user->role) {
        //         $user->syncRoles([$user->role]);
        //     }
        // });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function corporateOrganization()
    {
        return $this->belongsTo(CorporateOrganization::class);
    }

    const STATUS_ONLINE = 'online';

    const STATUS_OFFLINE = 'offline';

    const STATUS_ON_LEAVE = 'on_leave';

    const STATUS_SUSPENDED = 'suspended';

    public const DEPARTMENTS = [
        'super_admin' => 'Super Admin',
        'admin' => 'Admin',
        'reception' => 'Reception',
        'housekeeping' => 'Housekeeping',
        'accounting' => 'Accounting',
        'management' => 'Management',
        'kitchen' => 'Kitchen',
        'kitchen_manager' => 'Kitchen Manager',
        'kitchen_staff' => 'Kitchen Staff',
        'guest' => 'Guest',
    ];

    public const DEPARTMENT_ROLE_MAP = [
        'super_admin' => 'super_admin',
        'admin' => 'admin',
        'reception' => 'receptionist',
        'housekeeping' => 'housekeeping',
        'accounting' => 'accountant',
        'management' => 'manager',
        'kitchen' => 'kitchen_staff',
        'kitchen_manager' => 'kitchen_manager',
        'kitchen_staff' => 'kitchen_staff',
        'guest' => 'guest',
    ];

    // Relationships
    public function guest()
    {
        return $this->hasOne(Guest::class);
    }

    // public function getOrCreateGuest()
    // {
    //     return $this->guest ?? Guest::create([
    //         'user_id' => $this->id,
    //         'first_name' => $this->first_name,
    //         'last_name' => $this->last_name,
    //         'email' => $this->email,
    //     ]);
    // }

    public static function getDepartments(): array
    {
        return self::DEPARTMENTS;
    }

    public static function getGuestDepartment(): array
    {
        return ['guest' => 'Guest'];
    }

    public function getDepartmentLabelAttribute()
    {
        return self::getDepartments()[$this->department] ?? $this->department;
    }

    public function getNameAttribute(): string
    {
        return trim(($this->first_name ?? '').' '.($this->last_name ?? '')) ?: 'User';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept(['password', 'remember_token'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "User {$eventName}");
    }

    public function getRoleAttribute()
    {
        return $this->roles->pluck('name')->first();
    }

    // public function getRoleNameAttribute()
    // {
    //     return $this->roles->pluck('name')->first();
    // }

    public function getRoleNameAttribute(): string
    {
        return str($this->roles->pluck('name')->first() ?? 'guest')
            ->replace('_', ' ')
            ->headline()
            ->toString();
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isManager(): bool
    {
        return $this->hasRole('manager');
    }

    public function isReceptionist(): bool
    {
        return $this->hasRole('receptionist');
    }

    public function isAccountant(): bool
    {
        return $this->hasRole('accountant');
    }

    public function isGuest(): bool
    {
        // If user has no staff role → treat as guest
        return ! $this->hasAnyRole([
            'super_admin',
            'admin',
            'manager',
            'receptionist',
            'accountant',
            'housekeeping',
            'kitchen_staff',
            'kitchen_manager',
        ]);
    }

    /**
     * Determine whether this guest has completed authenticator-app setup.
     */
    public function twoFactorEnabled(): bool
    {
        return $this->isGuest()
            && filled($this->two_factor_secret)
            && $this->two_factor_confirmed_at !== null;
    }

    public function isOnline(): bool
    {
        return $this->last_seen_at &&
            $this->last_seen_at->gt(now()->subMinutes(5));
    }

    public function isStaff(): bool
    {
        return $this->hasAnyRole([
            'super_admin',
            'admin',
            'manager',
            'receptionist',
            'accountant',
            'housekeeping',
            'kitchen_staff',
            'kitchen_manager',
        ]);
    }

    public function activities()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->status === self::STATUS_SUSPENDED) {
            return false;
        }

        return $this->hasAnyRole([
            'super_admin',
            'admin',
            'manager',
            'receptionist',
            'accountant',
            'housekeeping',
            'kitchen_staff',
            'kitchen_manager',
            // 'security',
        ]);
    }
}
