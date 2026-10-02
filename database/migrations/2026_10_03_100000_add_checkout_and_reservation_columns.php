<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('type', 20)->default('purchase')->after('number')->comment('purchase | reservation | deposit_reservation');
            $table->char('access_token', 40)->nullable()->unique()->after('type')->comment('Opens the order page from emails and SMS');
            $table->string('payment_method', 30)->nullable()->after('payment_status');
            $table->decimal('deposit_total', 12, 2)->default(0)->after('grand_total');
            $table->decimal('amount_paid', 12, 2)->default(0)->after('deposit_total');
            // Until when the pieces are held for this order; past it, the order is cancelled automatically.
            $table->dateTime('hold_expires_at')->nullable()->after('placed_at');
            // Advance reservations: end of the reservation period (reminders follow until hold_expires_at).
            $table->dateTime('reserved_until')->nullable()->after('hold_expires_at');
            $table->dateTime('reminded_at')->nullable()->after('reserved_until');

            $table->index(['status', 'hold_expires_at']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('purpose', 20)->default('full')->after('method')->comment('full | deposit | balance');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('purpose');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status', 'hold_expires_at']);
            $table->dropUnique(['access_token']);
            $table->dropColumn(['type', 'access_token', 'payment_method', 'deposit_total', 'amount_paid', 'hold_expires_at', 'reserved_until', 'reminded_at']);
        });
    }
};
