<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Panel;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\Guest;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
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
        'phone_number',
        'id_number',
        'password',
        // 'role',
        'status',
        'shift',
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

            $user->syncRoles([
                $role
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
                !$user->guest()->exists()
            ) {

                $guest = Guest::create([

                    'user_id' =>
                        $user->id,

                    'first_name' =>
                        $user->first_name
                        ?? 'Guest',

                    'last_name' =>
                        $user->last_name
                        ?? '',

                    'email' =>
                        $user->email,

                    'phone_number' =>
                        $user->phone_number,

                    'id_number' =>
                        $user->id_number,

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

                $user->syncRoles([
                    $role
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
        ];
    }

    const STATUS_ONLINE = 'online';
    const STATUS_OFFLINE = 'offline';
    const STATUS_ON_LEAVE = 'on_leave';
    const STATUS_SUSPENDED = 'suspended';
    const SHIFT_MORNING = 'morning';
    const SHIFT_EVENING = 'evening';
    const SHIFT_NIGHT = 'night';
    const SHIFT_OFF_DUTY = 'off_duty';

    public const DEPARTMENTS = [
        'super_admin' => 'Super Admin',
        'admin' => 'Admin',
        'reception' => 'Reception',
        'housekeeping' => 'Housekeeping',
        'accounting' => 'Accounting',
        'management' => 'Management',
        'kitchen' => 'Kitchen',
        'guest' => 'Guest',
    ];

    public const DEPARTMENT_ROLE_MAP = [
        'super_admin' => 'super_admin',
        'admin' => 'admin',
        'reception' => 'receptionist',
        'housekeeping' => 'housekeeper',
        'accounting' => 'accountant',
        'management' => 'manager',
        'kitchen' => 'kitchen_staff',
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
        return [
            'super_admin' => 'Super Admin',
            'admin' => 'Admin',
            'reception' => 'Reception',
            'housekeeping' => 'Housekeeping',
            'accounting' => 'Accounting',
            'management' => 'Management',
            'kitchen' => 'Kitchen',
            'guest' => 'Guest',
        ];
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
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? '')) ?: 'User';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "User {$eventName}");
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
        return !$this->hasAnyRole([
            'super_admin',
            'admin',
            'manager',
            'receptionist',
            'accountant',
            'housekeeping',
            'kitchen_staff',
        ]);
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
        ]);
    }

    public function activities()
    {
        return $this->hasMany(\App\Models\ActivityLog::class);
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
            // 'security',
        ]);
    }
}
