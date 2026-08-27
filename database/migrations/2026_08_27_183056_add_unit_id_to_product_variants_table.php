<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            // Nullable: existing variants carry their unit inside the free-text name.
            $table->foreignId('unit_id')->nullable()->after('weight_kg')
                ->constrained('units')->nullOnDelete();
            $table->decimal('unit_value', 10, 3)->nullable()->after('unit_id');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unit_id');
            $table->dropColumn('unit_value');
        });
    }
};
