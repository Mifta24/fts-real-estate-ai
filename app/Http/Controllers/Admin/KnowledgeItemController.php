<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesCurrentAgency;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveKnowledgeItemRequest;
use App\Models\AgencyKnowledgeItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KnowledgeItemController extends Controller
{
    use ResolvesCurrentAgency;

    public function index(Request $request): View
    {
        $agency = $this->currentAgency($request);

        $items = $agency->knowledgeItems()->orderBy('category')->orderBy('sort_order')->get();

        return view('admin.knowledge-items.index', compact('agency', 'items'));
    }

    public function create(Request $request): View
    {
        $agency = $this->currentAgency($request);

        return view('admin.knowledge-items.form', [
            'agency' => $agency,
            'item' => new AgencyKnowledgeItem(['category' => AgencyKnowledgeItem::CATEGORY_GENERAL, 'is_active' => true]),
            'categories' => AgencyKnowledgeItem::CATEGORIES,
        ]);
    }

    public function store(SaveKnowledgeItemRequest $request): RedirectResponse
    {
        $agency = $this->currentAgency($request);

        $item = $agency->knowledgeItems()->create($request->itemAttributes());

        return redirect()->route('admin.knowledge-items.index')->with('status', __('admin.knowledge.created', ['title' => $item->title]));
    }

    public function edit(Request $request, AgencyKnowledgeItem $knowledgeItem): View
    {
        $agency = $this->currentAgency($request);
        abort_if($knowledgeItem->agency_id !== $agency->id, 404);

        return view('admin.knowledge-items.form', [
            'agency' => $agency,
            'item' => $knowledgeItem,
            'categories' => AgencyKnowledgeItem::CATEGORIES,
        ]);
    }

    public function update(SaveKnowledgeItemRequest $request, AgencyKnowledgeItem $knowledgeItem): RedirectResponse
    {
        $agency = $this->currentAgency($request);
        abort_if($knowledgeItem->agency_id !== $agency->id, 404);

        $knowledgeItem->update($request->itemAttributes());

        return redirect()->route('admin.knowledge-items.index')->with('status', __('admin.knowledge.updated', ['title' => $knowledgeItem->title]));
    }

    public function destroy(Request $request, AgencyKnowledgeItem $knowledgeItem): RedirectResponse
    {
        $agency = $this->currentAgency($request);
        abort_if($knowledgeItem->agency_id !== $agency->id, 404);

        $knowledgeItem->delete();

        return redirect()->route('admin.knowledge-items.index')->with('status', __('admin.knowledge.deleted'));
    }
}
