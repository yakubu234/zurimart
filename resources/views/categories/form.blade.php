@extends('layouts.app')

@section('title', $category->exists ? 'Edit Product Type' : 'Add Product Type')
@section('page_title', $category->exists ? 'Edit Product Type' : 'Add Product Type')
@section('page_intro', 'Create or maintain the product types used across the bakery catalog.')

@section('page')
    <div class="card card-warning">
        <div class="card-header">
            <h3 class="card-title">{{ $category->exists ? 'Update Product Type' : 'Create Product Type' }}</h3>
        </div>
        <form action="{{ $category->exists ? route('categories.update', $category) : route('categories.store') }}" method="POST">
            @csrf
            @if ($category->exists)
                @method('PUT')
            @endif
            <div class="card-body">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $category->name) }}" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="4" class="form-control">{{ old('description', $category->description) }}</textarea>
                </div>
                <div class="form-group">
                    <label>Sort Order</label>
                    <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $category->sort_order ?? 0) }}" min="0" required>
                </div>
                <div class="form-check">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="category_active" @checked(old('is_active', $category->exists ? $category->is_active : true))>
                    <label class="form-check-label" for="category_active">Active product type</label>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-warning">{{ $category->exists ? 'Update Type' : 'Add Type' }}</button>
                <a href="{{ route('categories.index') }}" class="btn btn-default">Cancel</a>
            </div>
        </form>
    </div>
@endsection
