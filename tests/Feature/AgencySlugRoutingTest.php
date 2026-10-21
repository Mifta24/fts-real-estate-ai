<?php

namespace Tests\Feature;

use App\Models\Agency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
use Tests\TestCase;

class AgencySlugRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_top_level_route_segment_is_a_reserved_agency_slug(): void
    {
        $segments = collect(Route::getRoutes()->getRoutes())
            ->map(fn ($route) => explode('/', $route->uri())[0])
            ->reject(fn (string $segment) => $segment === '' || str_starts_with($segment, '{') || str_starts_with($segment, '_'))
            ->unique();

        $this->assertEmpty(
            $segments->diff(Agency::reservedSlugs())->values()->all(),
            'These top-level routes could be shadowed by an agency slug; add them to Agency::reservedSlugs().',
        );
    }

    public function test_an_agency_cannot_take_a_reserved_slug(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Agency::create(['name' => 'Admin Realty', 'slug' => 'admin', 'public_status' => 'published']);
    }

    public function test_generated_slugs_skip_reserved_words(): void
    {
        $this->assertSame('admin-1', Agency::generateUniqueSlug('Admin'));
    }

    public function test_agency_urls_only_match_plain_slugs(): void
    {
        Agency::create(['name' => 'Demo', 'slug' => 'demo', 'public_status' => 'published']);

        $this->get('/demo')->assertOk();
        $this->get('/Demo.php')->assertNotFound();
        $this->get('/up')->assertOk();
    }
}
