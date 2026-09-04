<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\RoomTypes\Schemas\RoomTypeForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Tests\TestCase;

class RoomTypeFormStructureTest extends TestCase
{
    public function test_room_type_form_groups_fields_into_responsive_workflow_sections(): void
    {
        $sections = RoomTypeForm::configure(Schema::make())->getComponents();

        self::assertCount(3, $sections);
        self::assertContainsOnlyInstancesOf(Section::class, $sections);
        self::assertSame(
            ['Room details', 'Guest media', 'Facilities & publishing'],
            array_map(fn (Section $section): string => $section->getHeading(), $sections),
        );

        self::assertSame(
            [
                ['name', 'price_per_night', 'capacity', 'description'],
                ['image', 'gallery'],
                ['facilities', 'is_published'],
            ],
            array_map(
                fn (Section $section): array => array_map(
                    fn ($component): string => $component->getName(),
                    $section->getDefaultChildComponents(),
                ),
                $sections,
            ),
        );

        foreach ($sections as $section) {
            self::assertSame(['default' => 1, 'md' => 2], $section->getColumns());
        }
    }

    public function test_facilities_and_publication_section_uses_the_full_available_width(): void
    {
        $sections = RoomTypeForm::configure(Schema::make())->getComponents();

        self::assertSame(['default' => 'full'], $sections[2]->getColumnSpan());
    }
}
