<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** "Sell with us": a customer offers a piece; staff review it and approve or decline. */
    public function up(): void
    {
        Schema::create('consignment_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('phone', 20);
            $table->string('email')->nullable();
            $table->string('city', 100);
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 190)->comment('What the piece is, in the owner\'s words');
            $table->text('description');
            $table->decimal('asking_price', 12, 2)->nullable();
            $table->string('status', 20)->default('new');
            $table->text('decision_note')->nullable()->comment('Sent to the customer with the decision');
            $table->text('admin_notes')->nullable()->comment('Internal only');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('decided_at')->nullable();
            $table->char('locale', 2)->default('ar');
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consignment_requests');
    }
};
