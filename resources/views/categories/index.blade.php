@extends('layouts.app')

@section('title', 'Product Types')
@section('page_title', 'Product Type Management')
@section('page_intro', 'Manage the product types that organize the bakery catalog, such as Core, Loaf, and Specialty.')

@section('page')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Product Types</h3>
            <div class="card-tools">
                <a href="{{ route('categories.create') }}" class="btn btn-warning btn-sm">Add Product Type</a>
            </div>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped table-hover text-nowrap">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Sort Order</th>
                        <th>Products</th>
                        <th>Status</th>
                        <th class="table-actions-col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categories as $category)
                        <tr>
                            <td>{{ $category->name }}</td>
                            <td>{{ $category->description ?: 'No description' }}</td>
                            <td>{{ $category->sort_order }}</td>
                            <td>{{ $category->products_count }}</td>
                            <td>
                                <span class="badge {{ $category->is_active ? 'badge-success' : 'badge-secondary' }}">
                                    {{ $category->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="table-actions-col">
                                <div class="action-buttons">
                                    <a href="{{ route('categories.edit', $category) }}" class="btn btn-info btn-sm action-icon-btn" title="Edit product type" aria-label="Edit product type">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Delete this product type?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm action-icon-btn" title="Delete product type" aria-label="Delete product type">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
