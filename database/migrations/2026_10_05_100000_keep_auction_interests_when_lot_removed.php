<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Removing a lot must not lose the people who registered for it: they stay on the auction's list. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auction_interests', function (Blueprint $table) {
            $table->dropForeign(['auction_lot_id']);
            $table->foreign('auction_lot_id')->references('id')->on('auction_lots')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('auction_interests', function (Blueprint $table) {
            $table->dropForeign(['auction_lot_id']);
            $table->foreign('auction_lot_id')->references('id')->on('auction_lots')->cascadeOnDelete();
        });
    }
};
