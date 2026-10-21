<?php

namespace App\Services\Advisor;

/**
 * Deterministic backstop for the "never state a price from memory" rule.
 * It reads the money amounts in a model reply ("Rp 2,5 M", "IDR 1.5M",
 * "850 juta", "3億円") and checks each one against the figures the reply may
 * legitimately use: tool results of this turn, earlier messages of the
 * conversation, and numbers the visitor typed. Amounts are matched
 * approximately because replies abbreviate ("2,35 M" for 2,347,000,000).
 */
class FigureGuard
{
    /**
     * Relative difference at which a stated amount still counts as the same figure.
     */
    private const TOLERANCE = 0.05;

    private const AMOUNT_PATTERN = '/(?<![\w.,])(rp\.?|idr|usd|jpy|¥|\$)?\s?(\d[\d.,]*\d|\d)\s?(?:(rb|ribu|juta|jt|miliar|milyar|billion|million|bn|m|b|k)(?![\p{L}\d])|(億|万))?/iu';

    /**
     * Unit words and what they multiply by. "m" and "b" are ambiguous between
     * languages (Indonesian "M" is miliar, English "M" is million), so they
     * carry every reading and an amount passes if any reading is backed.
     *
     * @var array<string, list<float>>
     */
    private const MULTIPLIERS = [
        'k' => [1e3], 'rb' => [1e3], 'ribu' => [1e3], '万' => [1e4],
        'jt' => [1e6], 'juta' => [1e6], 'million' => [1e6],
        '億' => [1e8],
        'miliar' => [1e9], 'milyar' => [1e9], 'billion' => [1e9], 'bn' => [1e9],
        'm' => [1e6, 1e9], 'b' => [1e9],
    ];

    /**
     * Units that make a bare number an amount without a currency in front.
     */
    private const SELF_EVIDENT_UNITS = ['rb', 'ribu', 'jt', 'juta', 'miliar', 'milyar', 'billion', 'million', 'bn', '億', '万'];

    /**
     * Money amounts in the text, each as the list of values it could mean.
     *
     * @return list<list<float>>
     */
    public function amountsIn(string $text): array
    {
        preg_match_all(self::AMOUNT_PATTERN, $text, $matches, PREG_SET_ORDER);

        $amounts = [];

        foreach ($matches as $match) {
            $currency = $match[1] ?? '';
            $unit = mb_strtolower(($match[3] ?? '') ?: ($match[4] ?? ''));

            if ($currency === '' && ! in_array($unit, self::SELF_EVIDENT_UNITS, true)) {
                continue;
            }

            $multipliers = self::MULTIPLIERS[$unit] ?? [1.0];
            $values = [];

            foreach ($this->readings($match[2]) as $number) {
                foreach ($multipliers as $multiplier) {
                    $values[] = $number * $multiplier;
                }
            }

            $amounts[] = $values;
        }

        return $amounts;
    }

    /**
     * Every figure in a piece of text that a reply may repeat: its money
     * amounts and its plain numbers.
     *
     * @return list<float>
     */
    public function figuresIn(string $text): array
    {
        $figures = [];

        foreach ($this->amountsIn($text) as $values) {
            array_push($figures, ...$values);
        }

        preg_match_all('/\d[\d.,]*\d|\d/u', $text, $numbers);

        foreach ($numbers[0] as $number) {
            array_push($figures, ...$this->readings($number));
        }

        return $figures;
    }

    /**
     * Every number and every amount inside a decoded tool result.
     *
     * @return list<float>
     */
    public function figuresInData(mixed $data): array
    {
        if (is_int($data) || is_float($data)) {
            return [(float) $data];
        }

        if (is_string($data)) {
            return $this->figuresIn($data);
        }

        $figures = [];

        foreach (is_array($data) ? $data : [] as $value) {
            array_push($figures, ...$this->figuresInData($value));
        }

        return $figures;
    }

    /**
     * True when the reply states an amount that is not among the backed figures.
     *
     * @param  list<float>  $backed
     */
    public function hasUnbackedAmount(string $reply, array $backed): bool
    {
        foreach ($this->amountsIn($reply) as $values) {
            if (! $this->isBacked($values, $backed)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Bracketed template placeholders such as "[price]" that the model leaves
     * in a reply instead of data.
     */
    public function hasPlaceholder(string $reply): bool
    {
        return preg_match('/\[[^\]]+\]/u', $reply) === 1;
    }

    /**
     * @param  list<float>  $values
     * @param  list<float>  $backed
     */
    private function isBacked(array $values, array $backed): bool
    {
        foreach ($values as $value) {
            foreach ($backed as $figure) {
                if (abs($value - $figure) <= max(self::TOLERANCE * abs($figure), 1.0)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * The values a written number can mean. "2.500.000" and "2,500,000" are
     * thousands separators, "2,5" and "2.5" are decimals, and a lone
     * separator followed by three digits ("1.500") could be either.
     *
     * @return list<float>
     */
    private function readings(string $raw): array
    {
        $raw = rtrim($raw, '.,');
        preg_match_all('/[.,]/', $raw, $separators, PREG_OFFSET_CAPTURE);
        $separators = $separators[0];

        if ($separators === []) {
            return [(float) $raw];
        }

        $last = end($separators);
        $integerReading = (float) preg_replace('/[.,]/', '', $raw);
        $decimalReading = (float) (preg_replace('/[.,]/', '', substr($raw, 0, $last[1])).'.'.substr($raw, $last[1] + 1));

        $sameKind = count(array_unique(array_column($separators, 0))) === 1;

        if (count($separators) > 1) {
            return $sameKind ? [$integerReading] : [$decimalReading];
        }

        return strlen($raw) - $last[1] - 1 === 3 ? [$integerReading, $decimalReading] : [$decimalReading];
    }
}
