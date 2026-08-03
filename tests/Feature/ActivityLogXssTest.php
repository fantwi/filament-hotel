<?php

namespace Tests\Feature;

use Tests\TestCase;

class ActivityLogXssTest extends TestCase
{
    public function test_activity_log_changes_are_html_escaped(): void
    {
        $html = view('filament.activity-diff', [
            'getState' => fn (): string => '<script>alert("xss")</script>',
        ])->render();

        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }
}
