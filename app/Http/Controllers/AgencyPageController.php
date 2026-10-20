<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\AgencyKnowledgeItem;
use App\Models\Property;
use App\Services\Viewing\ContactLinks;
use App\Services\Viewing\MortgageCalculator;
use App\Services\Viewing\ViewingService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AgencyPageController extends Controller
{
    public const SUPPORTED_LOCALES = ['id', 'en', 'ja'];

    public function __construct(
        private readonly ContactLinks $contactLinks,
        private readonly MortgageCalculator $mortgage,
        private readonly ViewingService $viewings,
    ) {}

    public function index(Request $request): View
    {
        $agencies = Agency::where('public_status', 'published')
            ->withCount(['properties' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('name')
            ->get();

        $locale = in_array($request->query('lang'), self::SUPPORTED_LOCALES, true)
            ? $request->query('lang')
            : ($agencies->first()?->default_locale ?? 'id');

        app()->setLocale($locale);

        return view('opening', [
            'agencies' => $agencies,
            'locale' => $locale,
            'supportedLocales' => self::SUPPORTED_LOCALES,
        ]);
    }

    public function home(Request $request, string $agencySlug): View
    {
        [$agency, $locale] = $this->resolveAgency($request, $agencySlug);
        $data = $this->showroomData($agency, $locale, 'home');

        return view('site.home', [
            ...$data,
            'featured' => $data['properties']->where('is_featured', true)->take(3)->values()->whenEmpty(fn () => $data['properties']->take(3)),
        ]);
    }

    public function listings(Request $request, string $agencySlug): View
    {
        [$agency, $locale] = $this->resolveAgency($request, $agencySlug);
        $data = $this->showroomData($agency, $locale, 'listings');

        return view('site.listings', [
            ...$data,
            'brief' => $this->listingsBrief($agency, $data['properties'], $locale),
            'initialListingType' => in_array($request->query('type'), Property::LISTING_TYPES, true) ? $request->query('type') : '',
        ]);
    }

    public function listing(Request $request, string $agencySlug, string $propertySlug): View
    {
        [$agency, $locale] = $this->resolveAgency($request, $agencySlug);
        $data = $this->showroomData($agency, $locale, 'listing');
        $properties = $data['properties'];
        $index = $properties->search(fn (Property $property) => $property->slug === $propertySlug);

        abort_if($index === false, 404);

        $property = $properties[$index];

        return view('site.listing', [
            ...$data,
            'property' => $property,
            'propertyIndex' => $index,
            'previousProperty' => $properties[($index - 1 + $properties->count()) % $properties->count()],
            'nextProperty' => $properties[($index + 1) % $properties->count()],
            'similar' => $properties->reject(fn (Property $other) => $other->is($property))
                ->sortBy(fn (Property $other) => ($other->property_type === $property->property_type ? 0 : 1).($other->listing_type === $property->listing_type ? 0 : 1))
                ->take(3)
                ->values(),
            'brief' => $this->listingBrief($agency, $property, $locale),
            'mortgageDefaults' => $property->isForSale() ? $this->mortgage->simulate((float) $property->price, 20, 15, (float) $agency->mortgage_rate) : null,
        ]);
    }

    public function area(Request $request, string $agencySlug): View
    {
        [$agency, $locale] = $this->resolveAgency($request, $agencySlug);
        $data = $this->showroomData($agency, $locale, 'area');

        return view('site.area', [
            ...$data,
            'brief' => $this->areaBrief($data['places'], $locale),
        ]);
    }

    public function place(Request $request, string $agencySlug, int $placeId): View
    {
        [$agency, $locale] = $this->resolveAgency($request, $agencySlug);
        $data = $this->showroomData($agency, $locale, 'place');
        $places = $data['places'];
        $index = $places->search(fn (AgencyKnowledgeItem $item) => $item->id === $placeId);

        abort_if($index === false, 404);

        return view('site.place', [
            ...$data,
            'place' => $places[$index],
            'placeIndex' => $index,
            'previousPlace' => $places[($index - 1 + $places->count()) % $places->count()],
            'nextPlace' => $places[($index + 1) % $places->count()],
        ]);
    }

    public function info(Request $request, string $agencySlug): View
    {
        [$agency, $locale] = $this->resolveAgency($request, $agencySlug);

        return view('site.info', [
            ...$this->showroomData($agency, $locale, 'info'),
            'brief' => $this->infoBrief($agency, $locale),
            'guideItems' => $agency->knowledgeItems()->where('is_active', true)
                ->whereIn('category', [AgencyKnowledgeItem::CATEGORY_BUYING, AgencyKnowledgeItem::CATEGORY_FINANCING, AgencyKnowledgeItem::CATEGORY_LEGAL, AgencyKnowledgeItem::CATEGORY_FAQ])
                ->orderBy('sort_order')->get()->groupBy('category'),
            'aboutItems' => $agency->knowledgeItems()->where('is_active', true)
                ->where('category', AgencyKnowledgeItem::CATEGORY_GENERAL)
                ->orderBy('sort_order')->get(),
        ]);
    }

    public function agents(Request $request, string $agencySlug): View
    {
        [$agency, $locale] = $this->resolveAgency($request, $agencySlug);

        return view('site.agents', $this->showroomData($agency, $locale, 'agents'));
    }

    public function viewing(Request $request, string $agencySlug): View
    {
        [$agency, $locale] = $this->resolveAgency($request, $agencySlug);

        return view('site.viewing', [
            ...$this->showroomData($agency, $locale, 'viewing'),
            'preselectedProperty' => (string) $request->query('property', ''),
            'earliestDate' => $this->viewings->earliestDate($agency)->toDateString(),
            'latestDate' => $this->viewings->latestDate($agency)->toDateString(),
        ]);
    }

    /**
     * @return array{0: Agency, 1: string}
     */
    private function resolveAgency(Request $request, string $agencySlug): array
    {
        $agency = Agency::where('slug', $agencySlug)->firstOrFail();

        abort_if(! $agency->isPublished(), 404);

        $locale = in_array($request->query('lang'), self::SUPPORTED_LOCALES, true)
            ? $request->query('lang')
            : $agency->default_locale;

        app()->setLocale($locale);

        return [$agency, $locale];
    }

    /**
     * Everything the shared showroom layout needs, for whichever page the
     * visitor is on.
     *
     * @return array<string, mixed>
     */
    private function showroomData(Agency $agency, string $locale, string $page): array
    {
        $properties = $agency->properties()
            ->where('is_active', true)
            ->with(['images' => fn ($q) => $q->orderBy('sort_order')])
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->get()
            ->values();

        $places = $agency->knowledgeItems()->where('is_active', true)
            ->where('category', AgencyKnowledgeItem::CATEGORY_NEIGHBOURHOOD)
            ->orderBy('sort_order')->get()->values();

        return [
            'agency' => $agency,
            'locale' => $locale,
            'page' => $page,
            'supportedLocales' => self::SUPPORTED_LOCALES,
            'properties' => $properties,
            'places' => $places,
            'menuItems' => $this->menu($agency, $locale),
            'contactLinks' => $this->contactLinks->forAgent($agency, $locale),
        ];
    }

    /**
     * @return list<array{key: string, label: string, href: string, tour: string}>
     */
    private function menu(Agency $agency, string $locale): array
    {
        $route = fn (string $name) => route($name, ['agencySlug' => $agency->slug, 'lang' => $locale]);

        return [
            ['key' => 'home', 'label' => __('site.nav.home'), 'href' => $route('agency.home'), 'tour' => __('site.narration.tour.home')],
            ['key' => 'listings', 'label' => __('site.nav.listings'), 'href' => $route('agency.listings'), 'tour' => __('site.narration.tour.listings')],
            ['key' => 'area', 'label' => __('site.nav.area'), 'href' => $route('agency.area'), 'tour' => __('site.narration.tour.area')],
            ['key' => 'info', 'label' => __('site.nav.info'), 'href' => $route('agency.info'), 'tour' => __('site.narration.tour.info')],
            ['key' => 'agents', 'label' => __('site.nav.agents'), 'href' => $route('agency.agents'), 'tour' => __('site.narration.tour.agents')],
        ];
    }

    /**
     * The advisor's spoken intro to the catalogue, built only from stored listings.
     *
     * @param  Collection<int, Property>  $properties
     */
    private function listingsBrief(Agency $agency, Collection $properties, string $locale): ?string
    {
        $available = $properties->filter(fn (Property $property) => $property->isAvailable());

        if ($available->isEmpty()) {
            return null;
        }

        $cheapest = $available->sortBy('price')->first();

        return __($available->count() === 1 ? 'site.narration.listings_one' : 'site.narration.listings', [
            'count' => $available->count(),
            'sale' => $available->where('listing_type', Property::LISTING_SALE)->count(),
            'rent' => $available->where('listing_type', Property::LISTING_RENT)->count(),
            'price' => $agency->formatCompactMoney($cheapest->price, $locale).($cheapest->isForRent() ? ' '.__('site.common.per_year') : ''),
        ]);
    }

    /**
     * Every sentence is built from stored listing data, never generated.
     */
    private function listingBrief(Agency $agency, Property $property, string $locale): string
    {
        $parts = [__('site.narration.listing_type', [
            'type' => __('site.common.property_types.'.$property->property_type),
            'listing' => mb_strtolower(__('site.common.'.$property->listing_type)),
            'district' => $property->district ?: $agency->city,
        ])];

        if ($property->bedrooms > 0) {
            $parts[] = __('site.narration.listing_rooms', ['bedrooms' => $property->bedrooms, 'bathrooms' => $property->bathrooms]);
        }

        $parts[] = match (true) {
            $property->building_size_sqm && $property->land_size_sqm => __('site.narration.listing_size', ['building' => $property->building_size_sqm, 'land' => $property->land_size_sqm]),
            (bool) $property->building_size_sqm => __('site.narration.listing_building', ['building' => $property->building_size_sqm]),
            (bool) $property->land_size_sqm => __('site.narration.listing_land', ['land' => $property->land_size_sqm]),
            default => null,
        };

        if ($property->certificate) {
            $parts[] = __('site.narration.listing_certificate', ['certificate' => __('site.common.certificates.'.$property->certificate)]);
        }

        if ($property->isForSale()) {
            $simulation = $this->mortgage->simulate((float) $property->price, 20, 15, (float) $agency->mortgage_rate);
            $parts[] = __('site.narration.listing_price_sale', [
                'price' => $agency->formatCompactMoney($property->price, $locale),
                'monthly' => $agency->formatCompactMoney($simulation['monthly_installment'], $locale),
            ]);
        } else {
            $parts[] = __('site.narration.listing_price_rent', ['price' => $agency->formatCompactMoney($property->price, $locale)]);
        }

        if ($property->units_available > 1) {
            $parts[] = __('site.narration.listing_stock', ['count' => $property->units_available]);
        }

        return implode(' ', array_filter($parts));
    }

    /**
     * @param  Collection<int, AgencyKnowledgeItem>  $places
     */
    private function areaBrief(Collection $places, string $locale): ?string
    {
        if ($places->isEmpty()) {
            return null;
        }

        $examples = $places->take(3)->map(fn (AgencyKnowledgeItem $item) => $item->translatedTitle($locale))->implode($locale === 'ja' ? '、' : ', ');

        return __($places->count() === 1 ? 'site.narration.area_one' : 'site.narration.area', [
            'count' => $places->count(),
            'examples' => $examples,
        ]);
    }

    private function infoBrief(Agency $agency, string $locale): string
    {
        return __('site.narration.info', [
            'name' => $agency->name,
            'location' => collect([$agency->city, $agency->country])->filter()->implode($locale === 'ja' ? '、' : ', '),
            'opens' => substr((string) $agency->office_opens_at, 0, 5),
            'closes' => substr((string) $agency->office_closes_at, 0, 5),
        ]);
    }
}
