<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A landing page is a single URL built for one Facebook campaign: its own
 * headline, its own offer price, its own order form, and no way out of the page
 * except ordering.
 *
 * The copy is Bengali only. Every other content table on this site is
 * bilingual, but ad traffic is Bengali and a second set of boxes on an already
 * long form is a page nobody fills in.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            // What the page is called in the admin list. Never shown publicly,
            // so it can say "Eid mango combo - retargeting" without ceremony.
            $table->string('internal_name');
            $table->string('template', 32)->default('classic');

            // Hero
            $table->string('headline');
            $table->string('subheadline')->nullable();
            $table->string('badge_text', 100)->nullable();
            $table->string('hero_image')->nullable();
            $table->string('video_url')->nullable();
            $table->longText('body')->nullable();

            // Repeating blocks the admin fills in row by row.
            $table->json('features')->nullable();
            $table->json('faqs')->nullable();
            $table->json('reviews')->nullable();
            // Which blocks render, and in what order.
            $table->json('sections')->nullable();

            // How the items on the page are offered.
            $table->string('selection_mode', 16)->default('single');
            $table->decimal('bundle_price', 10, 2)->nullable();

            // Delivery: follow the shop's own charges, override them, or free.
            $table->string('delivery_mode', 16)->default('global');
            $table->decimal('delivery_inside', 10, 2)->nullable();
            $table->decimal('delivery_outside', 10, 2)->nullable();

            // Payment note is free text — usually a bKash number and how much
            // to send. Nothing is charged online.
            $table->string('payment_mode', 16)->default('cod');
            $table->decimal('advance_amount', 10, 2)->nullable();
            $table->text('payment_note')->nullable();

            $table->json('form_fields')->nullable();
            $table->string('cta_text')->nullable();

            // Urgency
            $table->timestamp('countdown_ends_at')->nullable();
            $table->string('stock_note')->nullable();

            // Tracking. Blank pixel falls back to the shop-wide one.
            $table->string('pixel_id', 32)->nullable();
            $table->string('thankyou_headline')->nullable();
            $table->text('thankyou_body')->nullable();

            // Sharing. Indexing is off by default: a campaign page competing
            // with the real product page in search helps nobody.
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('og_image')->nullable();
            $table->boolean('noindex')->default(true);

            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedBigInteger('views')->default(0);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at'], 'landing_pages_live_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_pages');
    }
};
