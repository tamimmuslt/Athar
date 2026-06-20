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
        Schema::create('donations', function (Blueprint $table) {
    $table->id();
    // 🔥 ربط التبرع بجدول المتطوعين الفعلي في سيستم Athar
    $table->foreignId('volunteer_id')->constrained('volunteers')->onDelete('cascade'); 
    $table->foreignId('campaign_id')->constrained('campaigns')->onDelete('cascade'); 
    $table->decimal('amount', 10, 2); 
    $table->enum('payment_method', ['credit_card', 'paypal', 'sham_cash']); 
    $table->text('optional_message')->nullable(); 
    $table->enum('status', ['pending', 'completed', 'failed'])->default('completed'); 
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
