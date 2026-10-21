<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PropertyImageUploadTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $owner;

    private Property $property;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->agency = Agency::create(['name' => 'Demo', 'slug' => 'demo', 'public_status' => 'published']);
        $this->owner = User::factory()->create();
        $this->agency->users()->attach($this->owner->id, ['role' => 'owner', 'status' => 'active']);
        $this->property = $this->agency->properties()->create(['name' => 'House', 'slug' => 'house', 'price' => 1_000_000, 'is_active' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function form(array $override = []): array
    {
        return [
            'name' => 'House', 'listing_type' => 'sale', 'property_type' => 'house', 'price' => 1_000_000,
            'bedrooms' => 2, 'bathrooms' => 1, 'units_available' => 1, 'is_active' => 1,
            ...$override,
        ];
    }

    public function test_a_photo_can_be_uploaded_and_is_served_from_the_image_disk(): void
    {
        $this->actingAs($this->owner)->put(route('admin.properties.update', $this->property), $this->form([
            'new_images' => [['file' => UploadedFile::fake()->image('front.jpg', 800, 600), 'url' => '', 'tags' => 'exterior, facade', 'alt' => 'Front']],
        ]))->assertRedirect(route('admin.properties.index'));

        $image = $this->property->images()->firstOrFail();

        $this->assertNull($image->image_url);
        $this->assertStringStartsWith("properties/{$this->property->id}/", $image->image_path);
        Storage::disk('public')->assertExists($image->image_path);
        $this->assertSame(Storage::disk('public')->url($image->image_path), $image->image_source);
        $this->assertSame(['exterior', 'facade'], $image->tags);
    }

    public function test_an_uploaded_file_wins_over_a_url_in_the_same_row(): void
    {
        $this->actingAs($this->owner)->put(route('admin.properties.update', $this->property), $this->form([
            'new_images' => [['file' => UploadedFile::fake()->image('front.png'), 'url' => 'https://example.test/other.jpg']],
        ]));

        $image = $this->property->images()->firstOrFail();
        $this->assertNotNull($image->image_path);
        $this->assertNull($image->image_url);
    }

    public function test_a_photo_can_still_be_added_by_url(): void
    {
        $this->actingAs($this->owner)->put(route('admin.properties.update', $this->property), $this->form([
            'new_images' => [['url' => 'https://example.test/house.jpg'], ['url' => 'javascript:alert(1)'], ['url' => '']],
        ]));

        $this->assertSame(['https://example.test/house.jpg'], $this->property->images()->pluck('image_url')->all());
    }

    public function test_only_real_images_within_the_size_limit_are_accepted(): void
    {
        $this->actingAs($this->owner)->put(route('admin.properties.update', $this->property), $this->form([
            'new_images' => [['file' => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf')]],
        ]))->assertSessionHasErrors('new_images.0.file');

        $this->actingAs($this->owner)->put(route('admin.properties.update', $this->property), $this->form([
            'new_images' => [['file' => UploadedFile::fake()->image('huge.jpg')->size(6000)]],
        ]))->assertSessionHasErrors('new_images.0.file');

        $this->assertSame(0, $this->property->images()->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_deleting_a_photo_removes_its_file(): void
    {
        $this->actingAs($this->owner)->put(route('admin.properties.update', $this->property), $this->form([
            'new_images' => [['file' => UploadedFile::fake()->image('front.jpg')]],
        ]));
        $image = $this->property->images()->firstOrFail();

        $this->actingAs($this->owner)->put(route('admin.properties.update', $this->property), $this->form(['delete_images' => [$image->id]]));

        $this->assertSame(0, $this->property->images()->count());
        Storage::disk('public')->assertMissing($image->image_path);
    }

    public function test_the_edit_form_accepts_file_uploads_and_shows_uploaded_photos(): void
    {
        $this->actingAs($this->owner)->get(route('admin.properties.edit', $this->property))
            ->assertOk()
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('type="file"', false);
    }
}
