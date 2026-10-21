<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Conversation;
use App\Models\HandoverRequest;
use App\Models\User;
use App\Models\ViewingAppointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminLocalizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function locales(): array
    {
        return ['id' => ['id'], 'en' => ['en'], 'ja' => ['ja']];
    }

    #[DataProvider('locales')]
    public function test_every_admin_screen_renders_translated_text_in_the_agency_language(string $locale): void
    {
        $agency = Agency::create(['name' => 'Demo', 'slug' => 'demo', 'public_status' => 'published', 'default_locale' => $locale]);
        $staff = User::factory()->create();
        $agency->users()->attach($staff->id, ['role' => 'owner', 'status' => 'active']);

        $property = $agency->properties()->create(['name' => 'House', 'slug' => 'house', 'price' => 1, 'is_active' => true, 'is_featured' => true]);
        $item = $agency->knowledgeItems()->create(['category' => 'neighbourhood', 'title' => 'Park', 'body' => 'Green', 'is_active' => true]);
        ViewingAppointment::create([
            'reference' => 'SV-1', 'agency_id' => $agency->id, 'property_id' => $property->id, 'visitor_name' => 'Budi', 'contact_type' => 'whatsapp',
            'visitor_phone' => '0812', 'payment_plan' => 'mortgage', 'viewing_date' => now()->addDays(3)->toDateString(), 'viewing_time' => '10:00',
        ]);
        $conversation = Conversation::create(['agency_id' => $agency->id, 'visitor_token' => (string) Str::uuid(), 'locale' => $locale]);
        $handover = HandoverRequest::create(['conversation_id' => $conversation->id, 'reason' => HandoverRequest::REASON_MORTGAGE_HELP, 'summary' => 'Needs help']);

        $urls = [
            route('admin.dashboard'), route('admin.properties.index'), route('admin.properties.create'), route('admin.properties.edit', $property),
            route('admin.knowledge-items.index'), route('admin.knowledge-items.create'), route('admin.knowledge-items.edit', $item),
            route('admin.viewings.index'), route('admin.handovers.index'), route('admin.handovers.show', $handover),
        ];

        foreach ($urls as $url) {
            $response = $this->actingAs($staff)->get($url)->assertOk()->assertSee(__('admin.nav.dashboard', [], $locale));

            $this->assertDoesNotMatchRegularExpression('/\badmin\.[a-z_]+\.[a-z_.]+/', strip_tags($response->getContent()), "Untranslated key on {$url}");
        }

        $this->actingAs($staff)->get(route('admin.handovers.show', $handover))->assertSee(__('admin.handover_reason.mortgage_help', [], $locale));
        $this->actingAs($staff)->get(route('admin.viewings.index'))->assertSee(__('admin.payment_plan.mortgage', [], $locale))->assertSee(__('admin.viewing_status.pending', [], $locale));
    }

    public function test_flash_messages_follow_the_agency_language(): void
    {
        $agency = Agency::create(['name' => 'Demo', 'slug' => 'demo', 'public_status' => 'published', 'default_locale' => 'id']);
        $staff = User::factory()->create();
        $agency->users()->attach($staff->id, ['role' => 'owner', 'status' => 'active']);
        $property = $agency->properties()->create(['name' => 'House', 'slug' => 'house', 'price' => 1]);

        $this->actingAs($staff)->delete(route('admin.properties.destroy', $property))->assertSessionHas('status', 'Listing dihapus.');
    }

    public function test_the_login_error_is_translated(): void
    {
        $this->post(route('admin.login.store'), ['email' => 'nobody@example.test', 'password' => 'wrong'])
            ->assertSessionHasErrors(['email' => __('admin.login.invalid')]);
    }

    public function test_every_admin_language_file_has_the_same_keys(): void
    {
        $keys = fn (string $locale) => array_keys(Arr::dot(require lang_path("{$locale}/admin.php")));

        $this->assertEqualsCanonicalizing($keys('en'), $keys('id'));
        $this->assertEqualsCanonicalizing($keys('en'), $keys('ja'));
    }
}
