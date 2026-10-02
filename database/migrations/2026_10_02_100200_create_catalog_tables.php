<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('slug_ar')->unique();
            $table->string('slug_en')->unique();
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $this->seoColumns($table);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['parent_id', 'is_active', 'sort_order']);
        });

        // Lookup tables for the attributes customers filter by
        foreach (['origins', 'eras', 'materials'] as $lookup) {
            Schema::create($lookup, function (Blueprint $table) use ($lookup) {
                $table->id();
                $table->string('name_ar');
                $table->string('name_en');
                $table->string('slug')->unique();
                if ($lookup === 'eras') {
                    $table->smallInteger('year_from')->nullable();
                    $table->smallInteger('year_to')->nullable();
                }
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('origin_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('era_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sku', 40)->unique();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('slug_ar')->unique();
            $table->string('slug_en')->unique();
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->text('story_ar')->nullable();
            $table->text('story_en')->nullable();
            $table->decimal('price', 12, 2)->comment('VAT inclusive, SAR');
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->timestamp('sale_starts_at')->nullable();
            $table->timestamp('sale_ends_at')->nullable();
            $table->unsignedInteger('stock_quantity')->default(1);
            $table->string('availability', 20)->default('available');
            $table->string('condition', 20)->nullable();
            $table->decimal('width_cm', 8, 2)->nullable();
            $table->decimal('height_cm', 8, 2)->nullable();
            $table->decimal('depth_cm', 8, 2)->nullable();
            $table->decimal('weight_kg', 8, 2)->nullable();
            $table->boolean('is_rare')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $this->seoColumns($table);
            $table->text('search_text')->nullable()->comment('Normalized AR/EN text maintained by the model');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
            $table->index(['category_id', 'status']);
            $table->index(['is_featured', 'status']);
            $table->index(['is_rare', 'status']);
            $table->index('price');
            $table->fullText('search_text');
        });

        Schema::create('material_product', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->primary(['product_id', 'material_id']);
        });

        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('slug_ar')->unique();
            $table->string('slug_en')->unique();
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $this->seoColumns($table);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('collection_product', function (Blueprint $table) {
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->primary(['collection_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_product');
        Schema::dropIfExists('collections');
        Schema::dropIfExists('material_product');
        Schema::dropIfExists('products');
        Schema::dropIfExists('materials');
        Schema::dropIfExists('eras');
        Schema::dropIfExists('origins');
        Schema::dropIfExists('categories');
    }

    private function seoColumns(Blueprint $table): void
    {
        $table->string('meta_title_ar')->nullable();
        $table->string('meta_title_en')->nullable();
        $table->string('meta_description_ar', 500)->nullable();
        $table->string('meta_description_en', 500)->nullable();
    }
};
