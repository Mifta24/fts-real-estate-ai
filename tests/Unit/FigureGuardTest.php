<?php

namespace Tests\Unit;

use App\Services\Advisor\FigureGuard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FigureGuardTest extends TestCase
{
    /**
     * @return array<string, array{string, float}>
     */
    public static function writtenAmounts(): array
    {
        return [
            'indonesian miliar' => ['Harganya Rp 2,5 miliar saja', 2_500_000_000],
            'indonesian M' => ['Rp 2,35 M', 2_350_000_000],
            'juta' => ['cicilan sekitar 12,5 juta per bulan', 12_500_000],
            'jt' => ['Rp 850 jt', 850_000_000],
            'dotted thousands' => ['Rp 2.500.000.000', 2_500_000_000],
            'comma thousands' => ['IDR 2,500,000,000', 2_500_000_000],
            'english B' => ['IDR 2.35B', 2_350_000_000],
            'english billion' => ['about 2.5 billion rupiah', 2_500_000_000],
            'english M' => ['IDR 1.5M', 1_500_000],
            'ribu' => ['biaya 500 ribu', 500_000],
            'dollar' => ['$150,000', 150_000],
            'japanese oku' => ['3億円', 300_000_000],
            'japanese man' => ['850万ルピア', 8_500_000],
        ];
    }

    #[DataProvider('writtenAmounts')]
    public function test_written_amounts_are_read(string $text, float $expected): void
    {
        $readings = (new FigureGuard)->amountsIn($text);

        $this->assertCount(1, $readings);
        $this->assertContains($expected, array_map('floatval', $readings[0]));
    }

    public function test_plain_numbers_are_not_amounts(): void
    {
        $this->assertSame([], (new FigureGuard)->amountsIn('3 bedrooms, 120 m² building, built in 2019, open 09:00 to 16:00'));
    }

    public function test_an_amount_that_a_tool_returned_is_backed_even_when_abbreviated(): void
    {
        $guard = new FigureGuard;
        $backed = $guard->figuresInData(['price' => 2_347_000_000.0, 'monthly_installment' => 12_480_000]);

        $this->assertFalse($guard->hasUnbackedAmount('Harganya Rp 2,35 M dengan cicilan Rp 12,5 juta', $backed));
        $this->assertFalse($guard->hasUnbackedAmount('It costs IDR 2.35B, about IDR 2,347,000,000.', $backed));
    }

    public function test_an_invented_amount_is_unbacked(): void
    {
        $guard = new FigureGuard;
        $backed = $guard->figuresInData(['price' => 2_347_000_000.0]);

        $this->assertTrue($guard->hasUnbackedAmount('Harganya hanya Rp 1,9 miliar', $backed));
        $this->assertTrue($guard->hasUnbackedAmount('Rp 2,35 M, DP cuma 50 juta', $backed));
        $this->assertTrue($guard->hasUnbackedAmount('Harga Rp 2 miliar', []));
    }

    public function test_amounts_the_visitor_typed_may_be_repeated(): void
    {
        $guard = new FigureGuard;
        $backed = $guard->figuresIn('Budget saya sekitar 1,5 miliar, DP 300 juta');

        $this->assertFalse($guard->hasUnbackedAmount('Dengan budget Rp 1,5 miliar dan DP 300 juta, ada dua pilihan.', $backed));
    }

    public function test_amounts_inside_tool_text_are_backed(): void
    {
        $guard = new FigureGuard;
        $backed = $guard->figuresInData(['body' => 'Biaya notaris sekitar Rp 15 juta, dibayar pembeli.']);

        $this->assertFalse($guard->hasUnbackedAmount('Biaya notaris kira-kira 15 juta rupiah.', $backed));
    }

    public function test_a_reply_without_amounts_passes(): void
    {
        $this->assertFalse((new FigureGuard)->hasUnbackedAmount('Rumah ini punya 3 kamar tidur dan taman luas.', []));
    }

    public function test_template_placeholders_are_detected(): void
    {
        $guard = new FigureGuard;

        $this->assertTrue($guard->hasPlaceholder('Harganya [price] per bulan'));
        $this->assertFalse($guard->hasPlaceholder('Harganya sudah tertera di kartu.'));
    }
}
