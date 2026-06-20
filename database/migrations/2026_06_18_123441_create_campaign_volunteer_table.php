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
    Schema::create('campaign_volunteer', function (Blueprint $table) {
        $table->id();
        
        // ربط معرف المتطوع ومعرف الحملة مع الحذف التلقائي عند الحذف
        $table->foreignId('volunteer_id')->constrained('volunteers')->onDelete('cascade');
        $table->foreignId('campaign_id')->constrained('campaigns')->onDelete('cascade');
        
        // الحقول المطلوبة لواجهتي المعلق والمنتهي
        $table->string('status')->default('pending'); // pending, accepted, cancelled
        $table->integer('hours_participated')->nullable()->default(0); // عدد ساعات التطوع
        $table->integer('rating')->nullable(); // التقييم من 1 إلى 5 نجوم
        
        $table->timestamps(); // لتوليد تاريخ التسجيل الحقيقي (created_at)
    });
}

public function down(): void
{
    Schema::dropIfExists('campaign_volunteer');
}
};
