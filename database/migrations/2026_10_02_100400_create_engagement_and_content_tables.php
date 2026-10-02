<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One audit trail for every status workflow (orders, finder requests, ...).
        Schema::create('status_changes', function (Blueprint $table) {
            $table->id();
            $table->morphs('subject');
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note', 1000)->nullable();
            $table->dateTime('created_at')->useCurrent();
        });

        Schema::create('finder_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('phone', 20);
            $table->string('email')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->text('description');
            $table->decimal('budget_min', 12, 2)->nullable();
            $table->decimal('budget_max', 12, 2)->nullable();
            $table->text('preferences')->nullable();
            $table->string('status', 20)->default('new');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('admin_notes')->nullable();
            $table->char('locale', 2)->default('ar');
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('auctions', function (Blueprint $table) {
            $table->id();
            $table->string('title_ar');
            $table->string('title_en');
            $table->string('slug_ar')->unique();
            $table->string('slug_en')->unique();
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('status', 20)->default('draft');
            $table->timestamps();

            $table->index(['status', 'starts_at']);
        });

        Schema::create('auction_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('lot_number');
            $table->string('title_ar');
            $table->string('title_en');
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->decimal('starting_price', 12, 2);
            $table->decimal('estimate_low', 12, 2)->nullable();
            $table->decimal('estimate_high', 12, 2)->nullable();
            $table->timestamps();

            $table->unique(['auction_id', 'lot_number']);
        });

        Schema::create('auction_interests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('auction_lot_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('phone', 20);
            $table->string('email')->nullable();
            $table->timestamps();

            $table->index(['auction_id', 'phone']);
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title_ar');
            $table->string('title_en');
            $table->string('slug_ar')->unique();
            $table->string('slug_en')->unique();
            $table->string('excerpt_ar', 500)->nullable();
            $table->string('excerpt_en', 500)->nullable();
            $table->longText('body_ar');
            $table->longText('body_en')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->string('meta_title_ar')->nullable();
            $table->string('meta_title_en')->nullable();
            $table->string('meta_description_ar', 500)->nullable();
            $table->string('meta_description_en', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique()->comment('about, services, shipping-policy, returns-policy, privacy, terms');
            $table->string('title_ar');
            $table->string('title_en');
            $table->string('slug_ar')->unique();
            $table->string('slug_en')->unique();
            $table->longText('body_ar');
            $table->longText('body_en')->nullable();
            $table->string('meta_title_ar')->nullable();
            $table->string('meta_title_en')->nullable();
            $table->string('meta_description_ar', 500)->nullable();
            $table->string('meta_description_en', 500)->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        Schema::create('hero_slides', function (Blueprint $table) {
            $table->id();
            $table->string('title_ar');
            $table->string('title_en');
            $table->string('subtitle_ar', 500)->nullable();
            $table->string('subtitle_en', 500)->nullable();
            $table->string('cta_label_ar', 50)->nullable();
            $table->string('cta_label_en', 50)->nullable();
            $table->string('cta_url', 500)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question_ar', 500);
            $table->string('question_en', 500);
            $table->text('answer_ar');
            $table->text('answer_en');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('subject')->nullable();
            $table->text('message');
            $table->string('status', 20)->default('new');
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 50);
            $table->string('key', 100);
            $table->text('value')->nullable();
            $table->boolean('is_encrypted')->default(false)->comment('Provider credentials are stored encrypted');
            $table->timestamps();

            $table->unique(['group', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('hero_slides');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('auction_interests');
        Schema::dropIfExists('auction_lots');
        Schema::dropIfExists('auctions');
        Schema::dropIfExists('finder_requests');
        Schema::dropIfExists('status_changes');
    }
};
