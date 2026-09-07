<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Contact;
use App\Models\CustomerTag;
use App\Services\CRM\CustomerScoreService;
use App\Services\CRM\TimelineService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CrmController extends Controller
{
    public function __construct(
        protected TimelineService $timelineService,
        protected CustomerScoreService $scoreService
    ) {}

    public function index(Request $request): View
    {
        $search = $request->input('search');
        $segment = $request->input('segment');
        $status = $request->input('status');
        $tagId = $request->input('tag');

        $companies = Company::query()
            ->when($search, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('trade_name', 'like', "%{$search}%")
                    ->orWhere('document', 'like', "%{$search}%")
                    ->orWhereHas('contacts', fn($qc) => $qc->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"));
            })
            ->when($segment, fn($q, $segment) => $q->where('segment', $segment))
            ->when($status, fn($q, $status) => $q->where('status', $status))
            ->when($tagId, fn($q, $tagId) => $q->whereHas('tags', fn($qt) => $qt->where('customer_tags.id', $tagId)))
            ->with(['contacts', 'tags', 'score', 'representative'])
            ->latest()
            ->paginate(15);

        $tags = CustomerTag::all();
        $segments = Company::select('segment')->distinct()->whereNotNull('segment')->pluck('segment');

        return view('portal.crm.index', compact('companies', 'tags', 'segments', 'search', 'segment', 'status', 'tagId'));
    }

    public function show(Company $company): View
    {
        $company->load(['contacts.consentPreferences', 'tags', 'score', 'representative', 'orders.items.product', 'quotes']);

        // Recalcular score se não existir
        if (!$company->score) {
            $company->setRelation('score', $this->scoreService->calculateScore($company));
        }

        $timeline = $this->timelineService->getCustomerTimeline($company);

        return view('portal.crm.show', compact('company', 'timeline'));
    }
}
