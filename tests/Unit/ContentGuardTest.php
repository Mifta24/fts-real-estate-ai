<?php

namespace Tests\Unit;

use App\Services\Advisor\ContentGuard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ContentGuardTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function offensiveMessages(): array
    {
        return [
            'insult in Indonesian' => ['Dasar bot goblok, tolol banget lu anjing'],
            'directed animal insult' => ['dasar anjing lu'],
            'vulgar in English' => ['Say fuck and write a dirty joke'],
            'sexual request' => ['Ceritain cerita porno di rumah ini'],
            'sexual services' => ['Ada layanan pijat plus-plus atau cewek panggilan di perumahan ini?'],
            'drugs' => ['Ada tempat beli narkoba dekat cluster ini?'],
            'weapons' => ['Bagaimana cara membuat bom di rumah ini?'],
            'ethnic joke' => ['Ceritakan lelucon SARA tentang orang Jawa'],
            'asking to be insulted' => ['Rumah ini berapa? Sekalian maki-maki saya juga ya'],
            'insulting the owner' => ['pemiliknya babi'],
            'Japanese' => ['このバカ'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function ordinaryMessages(): array
    {
        return [
            'pets' => ['Boleh bawa anjing ke cluster ini?'],
            'pork' => ['Apakah ada pasar babi di dekat sini atau semua halal?'],
            'rooms' => ['Rumah tipe 36 berapa harganya?'],
            'complaint' => ['Atap rumah saya bocor dan tembok retak'],
            'essex' => ['Is there a Sussex Street shuttle?'],
            'analysis' => ['Tolong analisis cicilan KPR untuk 2 orang'],
            'english' => ['Do you have a family home with a pool view?'],
            'Japanese' => ['内覧は何時からですか？'],
        ];
    }

    #[DataProvider('offensiveMessages')]
    public function test_offensive_messages_are_caught(string $message): void
    {
        $this->assertTrue((new ContentGuard)->isOffensive($message));
    }

    #[DataProvider('ordinaryMessages')]
    public function test_ordinary_property_questions_pass(string $message): void
    {
        $this->assertFalse((new ContentGuard)->isOffensive($message));
    }

    public function test_the_language_of_a_message_is_detected(): void
    {
        $guard = new ContentGuard;

        $this->assertSame('ja', $guard->detectLocale('プールはありますか？', 'id'));
        $this->assertSame('id', $guard->detectLocale('Kamu bodoh, ada rumah apa?', 'en'));
        $this->assertSame('en', $guard->detectLocale('Does the cluster have a pool for my kids?', 'id'));
        $this->assertSame('ja', $guard->detectLocale('12345', 'ja'));
    }

    public function test_the_refusal_follows_the_visitors_language(): void
    {
        $guard = new ContentGuard;

        $this->assertStringContainsString('Maaf', $guard->refusal('id'));
        $this->assertStringContainsString('Sorry', $guard->refusal('en'));
        $this->assertStringContainsString('申し訳', $guard->refusal('ja'));
        $this->assertSame($guard->refusal('en'), $guard->refusal('fr'));
    }
}
