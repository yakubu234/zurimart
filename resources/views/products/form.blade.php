@extends('layouts.app')

@section('title', $product->exists ? 'Edit Product' : 'Add Product')
@section('page_title', $product->exists ? 'Edit Product' : 'Add Product')
@section('page_intro', 'Maintain bakery products with proper pricing, stock, and product type assignment.')

@section('page')
    <div class="card card-warning card-outline">
        <div class="card-header">
            <h3 class="card-title">{{ $product->exists ? 'Update Product Details' : 'Create a New Product' }}</h3>
        </div>
        <form action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}" method="POST">
            @csrf
            @if ($product->exists)
                @method('PUT')
            @endif
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>SKU</label>
                            <input type="text" name="sku" class="form-control" value="{{ old('sku', $product->sku) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Product Name</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $product->name) }}" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Product Type</label>
                            <select name="category_id" class="form-control" required>
                                <option value="">Select type</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Weight (grams)</label>
                            <input type="number" name="weight_grams" class="form-control" value="{{ old('weight_grams', $product->weight_grams) }}" min="1" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Stock Units</label>
                            <input type="number" name="stock_units" class="form-control" value="{{ old('stock_units', $product->stock_units) }}" min="0" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Retail Price</label>
                            <input type="number" step="0.01" name="retail_price" class="form-control" value="{{ old('retail_price', $product->retail_price) }}" min="0" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Wholesale Price</label>
                            <input type="number" step="0.01" name="wholesale_price" class="form-control" value="{{ old('wholesale_price', $product->wholesale_price) }}" min="0" required>
                        </div>
                    </div>
                </div>
                <div class="form-check">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" @checked(old('is_active', $product->exists ? $product->is_active : true))>
                    <label class="form-check-label" for="is_active">Active product</label>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-warning">{{ $product->exists ? 'Update Product' : 'Add Product' }}</button>
                <a href="{{ route('products.index') }}" class="btn btn-default">Cancel</a>
            </div>
        </form>
    </div>
@endsection
