<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesCurrentAgency;
use App\Http\Controllers\Controller;
use App\Models\ViewingAppointment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ViewingController extends Controller
{
    use ResolvesCurrentAgency;

    private const STATUSES = [
        ViewingAppointment::STATUS_PENDING,
        ViewingAppointment::STATUS_CONFIRMED,
        ViewingAppointment::STATUS_COMPLETED,
        ViewingAppointment::STATUS_CANCELLED,
    ];

    public function index(Request $request): View
    {
        $agency = $this->currentAgency($request);

        $status = in_array($request->query('status'), self::STATUSES, true) ? $request->query('status') : null;

        $viewings = $agency->viewings()
            ->with('property')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('viewing_date')
            ->orderBy('viewing_time')
            ->paginate(20)
            ->withQueryString();

        return view('admin.viewings.index', compact('agency', 'viewings', 'status'));
    }

    public function updateStatus(Request $request, ViewingAppointment $viewing): RedirectResponse
    {
        $agency = $this->currentAgency($request);
        abort_if($viewing->agency_id !== $agency->id, 404);

        $data = $request->validate([
            'status' => ['required', Rule::in([ViewingAppointment::STATUS_CONFIRMED, ViewingAppointment::STATUS_COMPLETED, ViewingAppointment::STATUS_CANCELLED])],
        ]);

        $viewing->update(['status' => $data['status']]);

        return back()->with('status', __('admin.viewings.updated', ['reference' => $viewing->reference, 'status' => __('admin.viewing_status.'.$data['status'])]));
    }
}
