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
        Schema::table('volunteers', function (Blueprint $table) {
            // إضافة الحقول بعد حقل الهاتف (phone) دون التأثير على باقي الجدول
            $table->integer('age')->nullable()->after('phone');
            $table->enum('gender', ['male', 'female'])->nullable()->after('age');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('volunteers', function (Blueprint $table) {
            // في حال التراجع، نقوم بحذف الحقلين فقط
            $table->dropColumn(['age', 'gender']);
        });
    }
};