<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\BranchProductStockService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(BranchProductStockService $branchStocks): View
    {
        $products = Product::query()
            ->with('productCategory')
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        $branches = Branch::query()->pluck('id')->all();
        $stockMap = $branchStocks->stockMap(
            $branches,
            $products->pluck('id')->all(),
            stockDate: now()->toDateString()
        );

        $products->each(function (Product $product) use ($stockMap) {
            $currentStock = collect($stockMap)
                ->sum(fn (array $branchStock) => (int) ($branchStock[$product->id] ?? 0));

            $product->setAttribute('current_stock_units', $currentStock);
        });

        return view('products.index', compact('products'));
    }

    public function create(): View
    {
        $categories = ProductCategory::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();

        return view('products.form', ['product' => new Product(), 'categories' => $categories]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        Product::query()->create($data);

        return redirect()->route('products.index')->with('success', 'Product added successfully.');
    }

    public function edit(Product $product): View
    {
        $categories = ProductCategory::query()->orderBy('sort_order')->orderBy('name')->get();

        return view('products.form', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validatedData($request, $product->id);
        $product->update($data);

        return redirect()->route('products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        abort_unless(request()->user()?->canDeleteProducts(), 403);

        if ($product->orderItems()->exists()) {
            return back()->withErrors(['product' => 'This product is already used in orders and cannot be deleted. You can edit or deactivate it instead.']);
        }

        $product->delete();

        return redirect()->route('products.index')->with('success', 'Product deleted successfully.');
    }

    protected function validatedData(Request $request, ?int $productId = null): array
    {
        $data = $request->validate([
            'sku' => ['required', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($productId)],
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:product_categories,id'],
            'weight_grams' => ['required', 'integer', 'min:1'],
            'retail_price' => ['required', 'numeric', 'min:0'],
            'wholesale_price' => ['required', 'numeric', 'min:0'],
            'stock_units' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $category = ProductCategory::query()->findOrFail($data['category_id']);
        $data['category'] = $category->name;
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
