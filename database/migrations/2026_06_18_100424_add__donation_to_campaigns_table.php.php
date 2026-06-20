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
    // تحديد نوع الحملة (volunteer: تطوعية، donation: تبرعية)
    $table->enum('category', ['volunteer', 'donation'])->default('volunteer');
    
    // حقول الحملات التبرعية
    $table->decimal('donation_goal', 10, 2)->nullable();  // المبلغ المستهدف (مثال: 10000)
    $table->decimal('raised_amount', 10, 2)->default(0); // المبلغ الذي تم تجميعه
    $table->integer('donors_count')->default(0);          // عدد المتبرعين
    
    // تفاصيل الأثر والمساعدة (تخزن نصوص مفصولة بأسطر \n)
    $table->text('donation_benefits')->nullable();        // How Your Donation Helps
    $table->text('donation_impact')->nullable();          // Donation Impact (يمكن تخزينها كـ JSON أو نص منسق)
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
