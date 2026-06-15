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
        Schema::create('campaign_applications', function (Blueprint $table) {
    $table->id();
    $table->foreignId('volunteer_id')->constrained()->onDelete('cascade'); // المتطوع
    $table->foreignId('campaign_id')->constrained()->onDelete('cascade');
    $table->integer('score')->nullable(); // النتيجة المئوية للمتطوع %
    $table->enum('status', ['pending_test', 'passed', 'failed', 'approved_by_org', 'rejected_by_org'])
          ->default('pending_test');
    $table->date('submitted_at')->nullable();
    $table->timestamps();
    
    // منع المتطوع من تقديم أكثر من طلب لنفس الحملة
    $table->unique(['volunteer_id', 'campaign_id']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_applications');
    }
};
