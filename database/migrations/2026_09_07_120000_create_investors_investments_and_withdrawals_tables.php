<?php

use App\Support\PaymentAccounts;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who put money into the shop, and who took money out.
 *
 * Three tables rather than one for the same reason suppliers and purchases are
 * separate: the party is a thing you look up and keep, the movement is a thing
 * that happened on a date. A balance is then a question you ask an investor,
 * never a number anyone has to remember to keep in step.
 *
 * Deliberately not expenses: capital going in is not revenue and capital coming
 * out is not a cost, so neither belongs in the profit and loss figure. They are
 * real cash movements, though, so both show up in the account table underneath
 * it — which is what that table has always been for.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->text('notes')->nullable();
            // Someone who has been paid out in full stops appearing in the
            // pickers without their history going anywhere.
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });

        Schema::create('investments', function (Blueprint $table) {
            $table->id();
            // Restricted, not cascaded: money that came in is a fact, and
            // deleting the person must not quietly delete the record of it.
            $table->foreignId('investor_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('invested_at');
            // Which account it landed in, so the cash table can say where.
            $table->string('received_in', 20)->default(PaymentAccounts::DEFAULT_PAYOUT);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('invested_at');
            $table->index('received_in');
        });

        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investor_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('withdrawn_at');
            $table->string('paid_from', 20)->default(PaymentAccounts::DEFAULT_PAYOUT);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('withdrawn_at');
            $table->index('paid_from');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
        Schema::dropIfExists('investments');
        Schema::dropIfExists('investors');
    }
};
