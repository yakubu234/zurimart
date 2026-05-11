<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductCategoryController extends Controller
{
    public function index(): View
    {
        $categories = ProductCategory::query()
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('categories.form', ['category' => new ProductCategory()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        ProductCategory::query()->create($data);

        return redirect()->route('categories.index')->with('success', 'Product type added successfully.');
    }

    public function edit(ProductCategory $category): View
    {
        return view('categories.form', compact('category'));
    }

    public function update(Request $request, ProductCategory $category): RedirectResponse
    {
        $data = $this->validatedData($request, $category->id);
        $category->update($data);
        $category->products()->update(['category' => $data['name']]);

        return redirect()->route('categories.index')->with('success', 'Product type updated successfully.');
    }

    public function destroy(ProductCategory $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return back()->withErrors(['category' => 'You cannot delete a product type that still has products assigned to it.']);
        }

        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Product type deleted successfully.');
    }

    protected function validatedData(Request $request, ?int $categoryId = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('product_categories', 'name')->ignore($categoryId)],
            'description' => ['nullable', 'string'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['slug'] = Str::slug($data['name']);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
