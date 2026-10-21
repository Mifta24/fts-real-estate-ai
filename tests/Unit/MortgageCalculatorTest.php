<?php

namespace Tests\Unit;

use App\Services\Viewing\MortgageCalculator;
use PHPUnit\Framework\TestCase;

class MortgageCalculatorTest extends TestCase
{
    public function test_it_computes_a_fixed_rate_annuity(): void
    {
        $result = (new MortgageCalculator)->simulate(1_000_000_000, 20, 10, 7.5);

        $this->assertSame(200_000_000.0, $result['down_payment']);
        $this->assertSame(800_000_000.0, $result['loan_amount']);
        $this->assertEqualsWithDelta(9_498_000, $result['monthly_installment'], 10_000);
        $this->assertSame($result['monthly_installment'] * 120, $result['total_payment']);
        $this->assertEqualsWithDelta($result['total_payment'] - 800_000_000, $result['total_interest'], 0.01);
        $this->assertEqualsWithDelta($result['monthly_installment'] / 0.3, $result['minimum_monthly_income'], 1);
    }

    public function test_a_zero_rate_splits_the_loan_evenly(): void
    {
        $result = (new MortgageCalculator)->simulate(1_200_000_000, 10, 30, 0);

        $this->assertSame(3_000_000.0, $result['monthly_installment']);
        $this->assertSame(0, $result['total_interest']);
    }

    public function test_down_payment_and_tenor_are_clamped_to_the_allowed_range(): void
    {
        $low = (new MortgageCalculator)->simulate(1_000_000_000, 1, 0, 7.5);
        $high = (new MortgageCalculator)->simulate(1_000_000_000, 99, 99, 7.5);

        $this->assertEquals(10, $low['down_payment_percent']);
        $this->assertSame(MortgageCalculator::MIN_TENOR_YEARS, $low['tenor_years']);
        $this->assertEquals(90, $high['down_payment_percent']);
        $this->assertSame(MortgageCalculator::MAX_TENOR_YEARS, $high['tenor_years']);
    }

    public function test_a_longer_tenor_lowers_the_instalment_but_raises_the_interest(): void
    {
        $short = (new MortgageCalculator)->simulate(1_000_000_000, 20, 10, 7.5);
        $long = (new MortgageCalculator)->simulate(1_000_000_000, 20, 25, 7.5);

        $this->assertLessThan($short['monthly_installment'], $long['monthly_installment']);
        $this->assertGreaterThan($short['total_interest'], $long['total_interest']);
    }
}
