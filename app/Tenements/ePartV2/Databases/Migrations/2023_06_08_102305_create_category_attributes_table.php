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
        Schema::create('epart_v2_category_attributes', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('category_id')->index()->comment('Category ID');

            $table->string('name')->index()->comment('Attr name');
            $table->json('values')->comment('Attr values');

            $table->timestamps();

            $table->unique(['category_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('epart_v2_category_attributes');
    }
};
