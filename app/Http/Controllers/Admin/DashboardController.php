<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesCurrentAgency;
use App\Http\Controllers\Controller;
use App\Models\HandoverRequest;
use App\Models\ViewingAppointment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use ResolvesCurrentAgency;

    public function index(Request $request): View
    {
        $agency = $this->currentAgency($request);

        $stats = [
            'active_listings' => $agency->properties()->where('is_active', true)->count(),
            'knowledge_items' => $agency->knowledgeItems()->count(),
            'pending_viewings' => $agency->viewings()->where('status', ViewingAppointment::STATUS_PENDING)->count(),
            'open_handovers' => HandoverRequest::whereHas('conversation', fn ($q) => $q->where('agency_id', $agency->id))
                ->where('status', HandoverRequest::STATUS_OPEN)
                ->count(),
        ];

        $upcomingViewings = $agency->viewings()
            ->whereIn('status', [ViewingAppointment::STATUS_PENDING, ViewingAppointment::STATUS_CONFIRMED])
            ->whereDate('viewing_date', '>=', now($agency->timezone)->toDateString())
            ->orderBy('viewing_date')
            ->orderBy('viewing_time')
            ->take(5)
            ->with('property')
            ->get();

        $openHandovers = HandoverRequest::whereHas('conversation', fn ($q) => $q->where('agency_id', $agency->id))
            ->where('status', HandoverRequest::STATUS_OPEN)
            ->latest()
            ->take(5)
            ->with('conversation')
            ->get();

        return view('admin.dashboard', [
            'agency' => $agency,
            'stats' => $stats,
            'upcomingViewings' => $upcomingViewings,
            'openHandovers' => $openHandovers,
        ]);
    }
}
