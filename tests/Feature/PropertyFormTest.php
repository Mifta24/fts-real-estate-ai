<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyFormTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agency = Agency::create(['name' => 'Demo', 'slug' => 'demo', 'public_status' => 'published']);
        $this->owner = User::factory()->create();
        $this->agency->users()->attach($this->owner->id, ['role' => 'owner', 'status' => 'active']);
    }

    /**
     * @return array<string, mixed>
     */
    private function form(array $override = []): array
    {
        return [
            'name' => 'Garden House', 'listing_type' => 'sale', 'property_type' => 'house', 'price' => 2_000_000_000,
            'bedrooms' => 3, 'bathrooms' => 2, 'units_available' => 2, 'is_active' => 1,
            'amenities' => 'One Gate, Private Pool , ,garden',
            ...$override,
        ];
    }

    public function test_a_listing_is_created_with_a_unique_slug_and_normalised_amenities(): void
    {
        $this->actingAs($this->owner)->post(route('admin.properties.store'), $this->form())->assertRedirect(route('admin.properties.index'));
        $this->actingAs($this->owner)->post(route('admin.properties.store'), $this->form());

        $this->assertSame(['garden-house', 'garden-house-1'], $this->agency->properties()->orderBy('id')->pluck('slug')->all());
        $this->assertSame(['one_gate', 'private_pool', 'garden'], $this->agency->properties()->first()->amenities);
        $this->assertSame(0, $this->agency->properties()->first()->carports);
    }

    public function test_updating_a_listing_keeps_its_slug(): void
    {
        $this->actingAs($this->owner)->post(route('admin.properties.store'), $this->form());
        $property = $this->agency->properties()->firstOrFail();

        $this->actingAs($this->owner)->put(route('admin.properties.update', $property), $this->form(['name' => 'Renamed House', 'is_active' => 0]))->assertRedirect();

        $this->assertSame('garden-house', $property->fresh()->slug);
        $this->assertSame('Renamed House', $property->fresh()->name);
        $this->assertFalse($property->fresh()->is_active);
    }

    public function test_invalid_listings_are_rejected(): void
    {
        $this->actingAs($this->owner)->post(route('admin.properties.store'), $this->form(['listing_type' => 'swap', 'price' => -1, 'bedrooms' => '']))
            ->assertSessionHasErrors(['listing_type', 'price', 'bedrooms']);

        $this->assertSame(0, $this->agency->properties()->count());
    }

    public function test_knowledge_items_are_validated_and_their_tags_split(): void
    {
        $this->actingAs($this->owner)->post(route('admin.knowledge-items.store'), ['category' => 'nonsense', 'title' => 'Park', 'body' => 'Green'])->assertSessionHasErrors('category');

        $this->actingAs($this->owner)->post(route('admin.knowledge-items.store'), ['category' => 'faq', 'title' => 'Park', 'body' => 'Green', 'tags' => 'taman, park , ', 'is_active' => 1])->assertRedirect();

        $item = $this->agency->knowledgeItems()->firstOrFail();
        $this->assertSame(['taman', 'park'], $item->tags);
        $this->assertTrue($item->is_active);
    }
}
