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
        Schema::table('campaigns', function (Blueprint $table) {
    $table->text('responsibilities')->nullable(); // المسؤوليات (Your Responsibilities)
    $table->text('important_notes')->nullable();  // ملاحظات هامة (Important Notes)
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
