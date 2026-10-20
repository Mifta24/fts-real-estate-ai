<?php

namespace App\Services\Advisor;

use App\Models\Agency;
use App\Models\AgencyKnowledgeItem;
use App\Models\Conversation;
use App\Models\HandoverRequest;
use App\Models\Property;
use App\Services\Viewing\MortgageCalculator;
use App\Services\Viewing\ViewingService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Throwable;

/**
 * Executes the AI Property Advisor's tools against this agency's controlled
 * data. Every listing, price, mortgage figure, viewing slot and agency fact
 * must pass through here — the model itself is never trusted to hold any of it.
 */
class PropertyAdvisorTools
{
    private const MAX_RESULTS = 6;

    public function __construct(
        private readonly Agency $agency,
        private readonly Conversation $conversation,
        private readonly string $locale,
        private readonly ViewingService $viewings = new ViewingService,
        private readonly MortgageCalculator $mortgage = new MortgageCalculator,
    ) {}

    /**
     * OpenAI-compatible function-calling schema (used by the local LM Studio
     * / Ollama endpoint via AdvisorService).
     */
    public static function definitions(): array
    {
        return array_map(
            fn (array $tool) => ['type' => 'function', 'function' => $tool],
            [
                [
                    'name' => 'search_knowledge',
                    'description' => 'Search the agency\'s approved knowledge base: about the agency, the neighbourhood (schools, hospitals, malls, toll roads, stations), the buying process, financing / KPR, legal matters (certificates, AJB, taxes) and FAQs. Always use this instead of answering such questions from memory. Returns up to 5 matching entries.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => ['type' => 'string', 'description' => 'Keywords from the visitor question, e.g. "sekolah internasional" or "booking fee"'],
                            'category' => [
                                'type' => 'string',
                                'enum' => AgencyKnowledgeItem::CATEGORIES,
                                'description' => 'Optional category filter',
                            ],
                        ],
                        'required' => ['query'],
                    ],
                ],
                [
                    'name' => 'search_properties',
                    'description' => 'Search the agency\'s live listings with real prices and stock. Use this whenever the visitor describes what they want (buy or rent, type, budget, bedrooms, area) rather than naming one listing.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'listing_type' => ['type' => 'string', 'enum' => Property::LISTING_TYPES, 'description' => 'sale = buy, rent = lease'],
                            'property_type' => ['type' => 'string', 'enum' => Property::PROPERTY_TYPES],
                            'max_price' => ['type' => 'number', 'description' => 'Upper budget in the agency currency (full number, e.g. 2000000000 for 2 billion IDR)'],
                            'min_price' => ['type' => 'number'],
                            'min_bedrooms' => ['type' => 'integer', 'minimum' => 0],
                            'keyword' => ['type' => 'string', 'description' => 'Optional area or feature, e.g. "BSD", "pool", "hook"'],
                        ],
                    ],
                ],
                [
                    'name' => 'get_property_detail',
                    'description' => 'Get full specifications and photos for one listing by its slug (from a previous search_properties result). Use this when the visitor wants to know more about, or see photos of, a specific property.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'property_slug' => ['type' => 'string'],
                            'image_tag' => ['type' => 'string', 'description' => 'Optional filter, e.g. "kitchen", "bedroom", "exterior", "pool"'],
                        ],
                        'required' => ['property_slug'],
                    ],
                ],
                [
                    'name' => 'simulate_mortgage',
                    'description' => 'Calculate an indicative KPR (mortgage) simulation for a listing that is for sale: down payment, loan, monthly instalment and the income a bank would expect. ALWAYS call this before stating any instalment or DP figure — never compute it yourself.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'property_slug' => ['type' => 'string'],
                            'down_payment_percent' => ['type' => 'number', 'minimum' => MortgageCalculator::MIN_DOWN_PAYMENT_PERCENT, 'maximum' => MortgageCalculator::MAX_DOWN_PAYMENT_PERCENT, 'description' => 'Default 20'],
                            'tenor_years' => ['type' => 'integer', 'minimum' => MortgageCalculator::MIN_TENOR_YEARS, 'maximum' => MortgageCalculator::MAX_TENOR_YEARS, 'description' => 'Default 15'],
                        ],
                        'required' => ['property_slug'],
                    ],
                ],
                [
                    'name' => 'check_viewing_slots',
                    'description' => 'Get the free viewing (site visit) times for one listing on a given date. Call this before promising any viewing time.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'property_slug' => ['type' => 'string'],
                            'date' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
                        ],
                        'required' => ['property_slug', 'date'],
                    ],
                ],
                [
                    'name' => 'create_viewing_request',
                    'description' => 'Book a viewing (site visit) request after the visitor confirmed the listing, date and time and you have their name and phone. Re-checks the slot first. No payment is taken — an agent confirms it.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'property_slug' => ['type' => 'string'],
                            'date' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
                            'time' => ['type' => 'string', 'enum' => ViewingService::TIME_SLOTS],
                            'visitor_name' => ['type' => 'string'],
                            'visitor_phone' => ['type' => 'string'],
                            'visitor_email' => ['type' => 'string'],
                            'party_size' => ['type' => 'integer', 'minimum' => 1, 'maximum' => ViewingService::MAX_PARTY_SIZE],
                            'payment_plan' => ['type' => 'string', 'enum' => ['cash', 'mortgage', 'undecided']],
                            'notes' => ['type' => 'string'],
                        ],
                        'required' => ['property_slug', 'date', 'time', 'visitor_name', 'visitor_phone'],
                    ],
                ],
                [
                    'name' => 'request_human_handover',
                    'description' => 'Hand this conversation over to a human property agent. Use this for price negotiation, help with a mortgage application, legal questions you cannot answer from the knowledge base, special requests, complaints, payment problems, or anything you cannot answer confidently. Always write a clear summary so the agent does not need to ask the visitor to repeat themselves.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'reason' => ['type' => 'string', 'enum' => HandoverRequest::REASONS],
                            'summary' => ['type' => 'string', 'description' => 'What the visitor wants and the relevant context gathered so far, written for an agent who has not seen this conversation'],
                        ],
                        'required' => ['reason', 'summary'],
                    ],
                ],
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{text: string, ui: array|null}
     */
    public function dispatch(string $name, array $input): array
    {
        return match ($name) {
            'search_knowledge' => $this->searchKnowledge($input),
            'search_properties' => $this->searchProperties($input),
            'get_property_detail' => $this->getPropertyDetail($input),
            'simulate_mortgage' => $this->simulateMortgage($input),
            'check_viewing_slots' => $this->checkViewingSlots($input),
            'create_viewing_request' => $this->createViewingRequest($input),
            'request_human_handover' => $this->requestHumanHandover($input),
            default => ['text' => "Unknown tool: {$name}", 'ui' => null],
        };
    }

    private function searchKnowledge(array $input): array
    {
        $query = trim((string) ($input['query'] ?? ''));
        $category = in_array($input['category'] ?? null, AgencyKnowledgeItem::CATEGORIES, true) ? $input['category'] : null;
        $words = collect(preg_split('/\s+/', Str::lower($query)))->filter(fn ($word) => mb_strlen($word) > 2);

        $items = $this->agency->knowledgeItems()
            ->where('is_active', true)
            ->when($category, fn ($q) => $q->where('category', $category))
            ->orderBy('sort_order')
            ->get()
            ->filter(function (AgencyKnowledgeItem $item) use ($query, $words) {
                if ($query === '') {
                    return true;
                }

                $haystack = Str::lower($item->title.' '.$item->body.' '.implode(' ', $item->tags ?? []));

                return Str::contains($haystack, Str::lower($query))
                    || $words->contains(fn ($word) => Str::contains($haystack, $word));
            })
            ->take(5);

        if ($items->isEmpty()) {
            return [
                'text' => 'No matching knowledge base entry was found. Do not guess the answer — tell the visitor you will check with an agent, or call request_human_handover.',
                'ui' => null,
            ];
        }

        $results = $items->map(fn (AgencyKnowledgeItem $item) => array_filter([
            'category' => $item->category,
            'title' => $item->translatedTitle($this->locale),
            'body' => $item->translatedBody($this->locale),
            'distance' => $item->distance,
        ]))->values()->all();

        return ['text' => json_encode($results, JSON_UNESCAPED_UNICODE), 'ui' => null];
    }

    private function searchProperties(array $input): array
    {
        $listingType = in_array($input['listing_type'] ?? null, Property::LISTING_TYPES, true) ? $input['listing_type'] : null;
        $propertyType = in_array($input['property_type'] ?? null, Property::PROPERTY_TYPES, true) ? $input['property_type'] : null;
        $maxPrice = is_numeric($input['max_price'] ?? null) ? (float) $input['max_price'] : null;
        $minPrice = is_numeric($input['min_price'] ?? null) ? (float) $input['min_price'] : null;
        $minBedrooms = (int) ($input['min_bedrooms'] ?? 0);
        $keyword = Str::lower(trim((string) ($input['keyword'] ?? '')));

        $matches = $this->agency->properties()
            ->where('is_active', true)
            ->where('units_available', '>', 0)
            ->when($listingType, fn ($q) => $q->where('listing_type', $listingType))
            ->when($propertyType, fn ($q) => $q->where('property_type', $propertyType))
            ->when($maxPrice, fn ($q) => $q->where('price', '<=', $maxPrice))
            ->when($minPrice, fn ($q) => $q->where('price', '>=', $minPrice))
            ->when($minBedrooms > 0, fn ($q) => $q->where('bedrooms', '>=', $minBedrooms))
            ->with('images')
            ->orderBy('price')
            ->get()
            ->filter(fn (Property $property) => $keyword === '' || Str::contains(
                Str::lower($property->name.' '.$property->district.' '.$property->description.' '.implode(' ', $property->amenities ?? [])),
                $keyword
            ))
            ->take(self::MAX_RESULTS)
            ->map(fn (Property $property) => $this->summary($property))
            ->values()
            ->all();

        if ($matches === []) {
            return [
                'text' => 'No active listing matches those criteria. Tell the visitor honestly, suggest relaxing one criterion (budget, area, bedrooms), or offer request_human_handover so an agent can look for off-market options.',
                'ui' => null,
            ];
        }

        return [
            'text' => json_encode($matches, JSON_UNESCAPED_UNICODE),
            'ui' => ['type' => 'property_results', 'properties' => $matches],
        ];
    }

    private function getPropertyDetail(array $input): array
    {
        $property = $this->findProperty((string) ($input['property_slug'] ?? ''));
        if (! $property) {
            return ['text' => 'Listing not found. Call search_properties to get valid slugs.', 'ui' => null];
        }

        $images = $property->images;
        $tag = $input['image_tag'] ?? null;
        if ($tag) {
            $tagged = $images->filter(fn ($image) => $image->hasTag($tag));
            $images = $tagged->isNotEmpty() ? $tagged : $images;
        }

        $detail = [
            ...$this->summary($property),
            'description' => $property->translatedDescription($this->locale),
            'floors' => $property->floors,
            'certificate' => $property->certificate,
            'furnishing' => $property->furnishing,
            'year_built' => $property->year_built,
            'amenities' => $property->amenities,
            'price_per_sqm' => $property->pricePerSqm(),
            'images' => $images->map(fn ($image) => [
                'url' => $image->image_source,
                'tags' => $image->tags,
                'alt' => $image->alt_text,
            ])->values()->all(),
        ];

        return [
            'text' => json_encode($detail, JSON_UNESCAPED_UNICODE),
            'ui' => ['type' => 'property_detail', 'property' => $detail],
        ];
    }

    private function simulateMortgage(array $input): array
    {
        $property = $this->findProperty((string) ($input['property_slug'] ?? ''));
        if (! $property) {
            return ['text' => 'Listing not found. Call search_properties to get valid slugs.', 'ui' => null];
        }

        if (! $property->isForSale()) {
            return ['text' => 'This listing is for rent, so a mortgage does not apply. Explain the rental price and terms instead.', 'ui' => null];
        }

        $simulation = $this->mortgage->simulate(
            (float) $property->price,
            (float) ($input['down_payment_percent'] ?? 20),
            (int) ($input['tenor_years'] ?? 15),
            (float) $this->agency->mortgage_rate,
        );

        $result = [
            'property_slug' => $property->slug,
            'name' => $property->translatedName($this->locale),
            ...$simulation,
            'currency' => $this->agency->currency,
            'note' => 'Indicative fixed-rate estimate. The bank sets the final rate, fees and approval.',
        ];

        return [
            'text' => json_encode($result, JSON_UNESCAPED_UNICODE),
            'ui' => ['type' => 'mortgage', 'simulation' => $result],
        ];
    }

    private function checkViewingSlots(array $input): array
    {
        $property = $this->findProperty((string) ($input['property_slug'] ?? ''));
        if (! $property) {
            return ['text' => 'Listing not found. Call search_properties to get valid slugs.', 'ui' => null];
        }

        $date = $this->parseDate((string) ($input['date'] ?? ''));
        if (! $date || ! $this->viewings->isWithinWindow($this->agency, $date)) {
            return [
                'text' => 'Viewings can be booked from '.$this->viewings->earliestDate($this->agency)->toDateString().' to '.$this->viewings->latestDate($this->agency)->toDateString().'. Ask the visitor for a date in that range.',
                'ui' => null,
            ];
        }

        $slots = $this->viewings->availableSlots($this->agency, $property, $date);

        $result = [
            'property_slug' => $property->slug,
            'name' => $property->translatedName($this->locale),
            'date' => $date->toDateString(),
            'available_times' => $slots,
            'message' => $slots === [] ? 'No free viewing time on this date. Offer another date.' : null,
        ];

        return [
            'text' => json_encode(array_filter($result, fn ($value) => $value !== null), JSON_UNESCAPED_UNICODE),
            'ui' => ['type' => 'viewing_slots', 'slots' => $result],
        ];
    }

    private function createViewingRequest(array $input): array
    {
        $property = $this->findProperty((string) ($input['property_slug'] ?? ''));
        if (! $property) {
            return ['text' => 'Listing not found. Call search_properties to get valid slugs.', 'ui' => null];
        }

        $date = $this->parseDate((string) ($input['date'] ?? ''));
        $time = (string) ($input['time'] ?? '');

        if (! $date || ! in_array($time, ViewingService::TIME_SLOTS, true)) {
            return ['text' => 'Invalid date or time. Call check_viewing_slots and use one of the returned times.', 'ui' => null];
        }

        $viewing = $this->viewings->createRequest($this->agency, $property, [
            'date' => $date,
            'time' => $time,
            'visitor_name' => (string) $input['visitor_name'],
            'visitor_phone' => (string) $input['visitor_phone'],
            'visitor_email' => $input['visitor_email'] ?? null,
            'contact_type' => 'whatsapp',
            'party_size' => (int) ($input['party_size'] ?? 1),
            'payment_plan' => $input['payment_plan'] ?? 'undecided',
            'notes' => $input['notes'] ?? null,
        ], $this->conversation);

        if (! $viewing) {
            return [
                'text' => 'That viewing time is no longer free. Call check_viewing_slots again and offer the visitor another time.',
                'ui' => null,
            ];
        }

        $payload = [
            'viewing_reference' => $viewing->reference,
            'property_slug' => $property->slug,
            'name' => $property->translatedName($this->locale),
            'date' => $viewing->viewing_date->toDateString(),
            'time' => $viewing->viewing_time,
            'party_size' => $viewing->party_size,
            'status' => 'pending_confirmation',
        ];

        return [
            'text' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'ui' => ['type' => 'viewing_confirmation', 'viewing' => $payload],
        ];
    }

    private function requestHumanHandover(array $input): array
    {
        $reason = in_array($input['reason'] ?? null, HandoverRequest::REASONS, true) ? $input['reason'] : HandoverRequest::REASON_LOW_CONFIDENCE;
        $summary = (string) ($input['summary'] ?? '');

        HandoverRequest::create([
            'conversation_id' => $this->conversation->id,
            'reason' => $reason,
            'summary' => $summary,
            'status' => HandoverRequest::STATUS_OPEN,
        ]);

        $this->conversation->update([
            'status' => Conversation::STATUS_HANDED_OVER,
            'handover_summary' => $summary,
        ]);

        return [
            'text' => json_encode([
                'ok' => true,
                'message' => 'A property agent has been notified and will join this conversation shortly.',
            ], JSON_UNESCAPED_UNICODE),
            'ui' => ['type' => 'handover', 'reason' => $reason, 'summary' => $summary],
        ];
    }

    // --- helpers ---

    /**
     * The fields a listing card needs, shared by search and detail results.
     *
     * @return array<string, mixed>
     */
    private function summary(Property $property): array
    {
        return [
            'property_slug' => $property->slug,
            'name' => $property->translatedName($this->locale),
            'listing_type' => $property->listing_type,
            'property_type' => $property->property_type,
            'district' => $property->district,
            'price' => (float) $property->price,
            'price_period' => $property->isForRent() ? 'year' : null,
            'currency' => $this->agency->currency,
            'bedrooms' => $property->bedrooms,
            'bathrooms' => $property->bathrooms,
            'building_size_sqm' => $property->building_size_sqm,
            'land_size_sqm' => $property->land_size_sqm,
            'units_available' => $property->units_available,
            'thumbnail_url' => $property->images->first()?->image_source,
        ];
    }

    private function findProperty(string $slug): ?Property
    {
        return $this->agency->properties()->where('is_active', true)->where('slug', $slug)->with('images')->first();
    }

    private function parseDate(string $value): ?CarbonImmutable
    {
        try {
            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1
                ? CarbonImmutable::createFromFormat('Y-m-d', $value, $this->agency->timezone)->startOfDay()
                : null;
        } catch (Throwable) {
            return null;
        }
    }
}
