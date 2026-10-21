<?php

namespace App\Http\Middleware;

use App\Http\Controllers\AgencyPageController;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Staff see the admin panel in their agency's default language.
 */
class SetAdminLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->currentAgency()?->default_locale;

        if (in_array($locale, AgencyPageController::SUPPORTED_LOCALES, true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
