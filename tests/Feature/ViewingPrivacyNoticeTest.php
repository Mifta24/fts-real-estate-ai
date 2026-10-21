<?php

namespace Tests\Feature;

use App\Models\Agency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViewingPrivacyNoticeTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_viewing_form_tells_visitors_how_long_their_details_are_kept(): void
    {
        config(['retention.viewing_days' => 180]);
        Agency::create(['name' => 'Demo', 'slug' => 'demo', 'public_status' => 'published']);

        $this->get('/demo/viewing?lang=id')->assertOk()->assertSee(__('site.viewing.privacy', ['days' => 180], 'id'));
        $this->get('/demo/viewing?lang=ja')->assertOk()->assertSee(__('site.viewing.privacy', ['days' => 180], 'ja'));
    }
}
