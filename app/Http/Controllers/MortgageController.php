<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Services\Viewing\MortgageCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MortgageController extends Controller
{
    public function __construct(private readonly MortgageCalculator $mortgage) {}

    /**
     * The KPR widget on a listing page. Prices always come from the listing,
     * never from the browser.
     */
    public function simulate(Request $request, string $agencySlug): JsonResponse
    {
        $agency = Agency::where('slug', $agencySlug)->first();

        abort_if(! $agency || ! $agency->isPublished(), 404);

        $data = $request->validate([
            'property_slug' => ['required', 'string', 'max:120'],
            'down_payment_percent' => ['required', 'numeric', 'min:'.MortgageCalculator::MIN_DOWN_PAYMENT_PERCENT, 'max:'.MortgageCalculator::MAX_DOWN_PAYMENT_PERCENT],
            'tenor_years' => ['required', 'integer', 'min:'.MortgageCalculator::MIN_TENOR_YEARS, 'max:'.MortgageCalculator::MAX_TENOR_YEARS],
        ]);

        $property = $agency->properties()->where('is_active', true)->where('slug', $data['property_slug'])->first();

        abort_if(! $property || ! $property->isForSale(), 404);

        return response()->json([
            ...$this->mortgage->simulate((float) $property->price, (float) $data['down_payment_percent'], (int) $data['tenor_years'], (float) $agency->mortgage_rate),
            'currency' => $agency->currency,
        ]);
    }
}
