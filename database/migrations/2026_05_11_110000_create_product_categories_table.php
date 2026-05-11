<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('category')->constrained('product_categories')->nullOnDelete();
        });

        $categories = DB::table('products')
            ->select('category')
            ->distinct()
            ->whereNotNull('category')
            ->pluck('category');

        foreach ($categories as $index => $category) {
            $id = DB::table('product_categories')->insertGetId([
                'name' => $category,
                'slug' => \Illuminate\Support\Str::slug($category),
                'sort_order' => $index + 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('products')->where('category', $category)->update(['category_id' => $id]);
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });

        Schema::dropIfExists('product_categories');
    }
};
