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
    Schema::table('organizations', function (Blueprint $table) {
        // حقول لتخزين أرقام الحسابات أو العناوين الرقمية للمؤسسة
        $table->string('sham_cash_number')->nullable();
        $table->string('sham_cash_qrcode')->nullable(); // مسار صورة الباركود
        $table->string('paypal_email')->nullable();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            //
        });
    }
};
