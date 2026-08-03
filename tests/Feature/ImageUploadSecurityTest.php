<?php

namespace Tests\Feature;

use Tests\TestCase;

class ImageUploadSecurityTest extends TestCase
{
    public function test_filament_image_uploads_have_consistent_safety_limits(): void
    {
        $schemaPaths = [
            'app/Filament/Admin/Resources/Restaurants/Schemas/RestaurantForm.php',
            'app/Filament/Admin/Resources/ConferenceRooms/Schemas/ConferenceRoomForm.php',
            'app/Filament/Admin/Resources/MenuItems/Schemas/MenuItemForm.php',
            'app/Filament/Admin/Resources/RoomTypes/Schemas/RoomTypeForm.php',
            'app/Filament/Admin/Resources/RestaurantTables/Schemas/RestaurantTableForm.php',
        ];

        foreach ($schemaPaths as $path) {
            $source = file_get_contents(base_path($path));
            $uploadCount = substr_count($source, 'FileUpload::make');

            self::assertSame($uploadCount, substr_count($source, 'acceptedFileTypes('), $path);
            self::assertSame($uploadCount, substr_count($source, 'maxSize(5120)'), $path);
            self::assertSame($uploadCount, substr_count($source, "rules(['dimensions:max_width=4096,max_height=4096'])"), $path);
        }
    }

    public function test_guest_profile_upload_is_limited_to_safe_raster_images(): void
    {
        $source = file_get_contents(base_path('routes/guest.php'));

        self::assertStringContainsString(
            "'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048|dimensions:max_width=4096,max_height=4096'",
            $source,
        );
    }
}
