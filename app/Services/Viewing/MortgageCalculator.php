<?php

namespace App\Services\Viewing;

/**
 * Indicative KPR (mortgage) simulation with a fixed-rate annuity. Both the
 * AI advisor and the listing page use it, so they always show the same
 * numbers. It is an estimate only — the bank sets the final rate and terms.
 */
class MortgageCalculator
{
    public const MIN_DOWN_PAYMENT_PERCENT = 10;

    public const MAX_DOWN_PAYMENT_PERCENT = 90;

    public const MIN_TENOR_YEARS = 1;

    public const MAX_TENOR_YEARS = 30;

    /**
     * Banks usually cap the instalment at about a third of monthly income.
     */
    public const INCOME_RATIO = 0.3;

    /**
     * @return array{price: float, down_payment_percent: float, down_payment: float, loan_amount: float, tenor_years: int, annual_rate: float, monthly_installment: float, total_payment: float, total_interest: float, minimum_monthly_income: float}
     */
    public function simulate(float $price, float $downPaymentPercent, int $tenorYears, float $annualRate): array
    {
        $downPaymentPercent = min(max($downPaymentPercent, self::MIN_DOWN_PAYMENT_PERCENT), self::MAX_DOWN_PAYMENT_PERCENT);
        $tenorYears = min(max($tenorYears, self::MIN_TENOR_YEARS), self::MAX_TENOR_YEARS);

        $downPayment = round($price * $downPaymentPercent / 100);
        $loan = $price - $downPayment;
        $months = $tenorYears * 12;
        $monthlyRate = $annualRate / 100 / 12;

        $installment = $monthlyRate > 0
            ? $loan * $monthlyRate / (1 - (1 + $monthlyRate) ** -$months)
            : $loan / $months;

        $installment = round($installment);
        $totalPayment = $installment * $months;

        return [
            'price' => $price,
            'down_payment_percent' => $downPaymentPercent,
            'down_payment' => $downPayment,
            'loan_amount' => $loan,
            'tenor_years' => $tenorYears,
            'annual_rate' => $annualRate,
            'monthly_installment' => $installment,
            'total_payment' => $totalPayment,
            'total_interest' => max(0, $totalPayment - $loan),
            'minimum_monthly_income' => round($installment / self::INCOME_RATIO),
        ];
    }
}
