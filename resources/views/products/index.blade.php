@extends('layouts.app')

@section('title', 'Products')
@section('page_title', 'Product Catalog Management')
@section('page_intro', 'Add, edit, deactivate, and review bakery products with clear category ownership and stock visibility.')

@section('page')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Products</h3>
            <div class="card-tools">
                <a href="{{ route('categories.index') }}" class="btn btn-default btn-sm">Manage Product Types</a>
                <a href="{{ route('products.create') }}" class="btn btn-warning btn-sm">Add Product</a>
            </div>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped table-hover text-nowrap">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Product</th>
                        <th>Type</th>
                        <th>Weight</th>
                        <th>Retail</th>
                        <th>Wholesale</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th class="table-actions-col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                        <tr>
                            <td>{{ $product->sku }}</td>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->productCategory?->name ?? $product->category }}</td>
                            <td>{{ $product->weight_grams }}g</td>
                            <td>N{{ number_format($product->retail_price, 0) }}</td>
                            <td>N{{ number_format($product->wholesale_price, 0) }}</td>
                            <td>
                                <span class="badge {{ $product->stock_units < 150 ? 'badge-danger' : 'badge-success' }}">
                                    {{ $product->stock_units }} units
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $product->is_active ? 'badge-success' : 'badge-secondary' }}">
                                    {{ $product->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="table-actions-col">
                                <div class="action-buttons">
                                    <a href="{{ route('products.edit', $product) }}" class="btn btn-info btn-sm action-icon-btn" title="Edit product" aria-label="Edit product">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @if (auth()->user()?->canDeleteProducts())
                                        <form action="{{ route('products.destroy', $product) }}" method="POST" onsubmit="return confirm('Delete this product?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm action-icon-btn" title="Delete product" aria-label="Delete product">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
