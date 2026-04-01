<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('name_en')->nullable()->after('name');
            $table->string('name_bn')->nullable()->after('name_en');
            $table->text('description_en')->nullable()->after('description');
            $table->text('description_bn')->nullable()->after('description_en');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('name_en')->nullable()->after('name');
            $table->string('name_bn')->nullable()->after('name_en');
            $table->text('short_description_en')->nullable()->after('short_description');
            $table->text('short_description_bn')->nullable()->after('short_description_en');
            $table->text('description_en')->nullable()->after('description');
            $table->text('description_bn')->nullable()->after('description_en');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['name_en', 'name_bn', 'description_en', 'description_bn']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['name_en', 'name_bn', 'short_description_en', 'short_description_bn', 'description_en', 'description_bn']);
        });
    }
};
