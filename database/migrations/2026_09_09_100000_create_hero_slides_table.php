<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_slides', function (Blueprint $table) {
            $table->id();
            $table->string('badge_en')->nullable();       // 100% Pure & Organic
            $table->string('badge_bn')->nullable();
            $table->string('title_en');                   // may carry <br> and <span>
            $table->string('title_bn')->nullable();
            $table->text('subtitle_en')->nullable();
            $table->text('subtitle_bn')->nullable();
            $table->string('image')->nullable();          // blank falls back to the shipped hero
            $table->string('button_text_en')->nullable();
            $table->string('button_text_bn')->nullable();
            $table->string('button_url')->nullable();     // blank sends the visitor to the shop
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // The front page asks for exactly this, on every request.
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_slides');
    }
};
