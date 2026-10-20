<article class="place-card">
    <div class="place-card-photo">
        @if ($place->image_url)<img src="{{ $place->image_url }}" alt="" loading="lazy">@endif
        <span class="place-icon"><x-icon :name="$place->iconName()" /></span>
    </div>
    <div class="place-card-body">
        <h3 class="place-card-title"><a href="{{ route('agency.place', ['agencySlug' => $agency->slug, 'placeId' => $place->id, 'lang' => $locale]) }}" data-page-link>{{ $place->translatedTitle($locale) }}</a></h3>
        @if ($place->distance)<span class="place-card-distance">{{ $place->distance }}</span>@endif
        <p class="place-card-text">{{ $place->translatedBody($locale) }}</p>
    </div>
</article>
