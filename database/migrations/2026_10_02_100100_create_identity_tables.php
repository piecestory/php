<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('one_time_passwords', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20);
            $table->string('purpose', 20);
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->dateTime('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['phone', 'purpose', 'expires_at']);
        });

        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label', 50)->nullable();
            $table->string('recipient_name');
            $table->string('phone', 20);
            $table->string('city', 100);
            $table->string('district', 100);
            $table->string('street')->nullable();
            $table->string('building_number', 10)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('additional_number', 10)->nullable();
            $table->string('short_address', 8)->nullable()->comment('Saudi National Address short code');
            $table->string('notes', 500)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('one_time_passwords');
    }
};
