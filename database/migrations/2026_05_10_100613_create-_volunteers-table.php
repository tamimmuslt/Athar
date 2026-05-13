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
    Schema::create('volunteers', function (Blueprint $table) {
        $table->id();
        $table->string('full_name'); // [cite: 15]
        $table->string('email')->unique(); // [cite: 11]
        $table->string('password');
        $table->string('phone')->nullable();
        $table->string('city')->nullable();
        $table->text('bio')->nullable();
        $table->string('profile_picture')->nullable();
        $table->string('verification_code')->nullable(); // للمرحلة الثانية في الواجهة
        $table->timestamp('email_verified_at')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::dropIfExists('volunteers');
    }
};
