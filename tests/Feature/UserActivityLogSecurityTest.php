<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class UserActivityLogSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_activity_logs_never_store_passwords_or_remember_tokens(): void
    {
        $user = User::factory()->create();

        $user->update([
            'password' => 'new-secure-password',
            'remember_token' => 'replacement-token',
        ]);

        $activities = Activity::query()
            ->where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->get();

        $this->assertNotEmpty($activities);

        foreach ($activities as $activity) {
            $properties = $activity->properties->toArray();

            foreach (['attributes', 'old'] as $section) {
                $this->assertArrayNotHasKey('password', $properties[$section] ?? []);
                $this->assertArrayNotHasKey('remember_token', $properties[$section] ?? []);
            }
        }
    }
}
