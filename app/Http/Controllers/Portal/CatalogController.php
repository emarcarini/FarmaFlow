<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\CommercialCampaign;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $category = $request->input('category');

        $products = Product::query()
            ->when($search, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->when($category, fn($q, $category) => $q->where('category', $category))
            ->with(['prices', 'campaignProducts.campaign'])
            ->paginate(15);

        $categories = Product::select('category')->distinct()->whereNotNull('category')->pluck('category');

        $campaigns = CommercialCampaign::where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->with('products.product')
            ->get();

        return view('portal.catalog.index', compact('products', 'categories', 'campaigns', 'search', 'category'));
    }
}
