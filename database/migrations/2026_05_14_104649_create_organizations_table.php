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
    Schema::create('organizations', function (Blueprint $table) {
        $table->id();
        $table->string('org_name');
        $table->string('official_email')->unique();
        $table->string('password');
        $table->string('phone_number');
        $table->string('address');
        $table->text('org_description');
        $table->string('verification_document')->nullable(); 
        
        $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
        
        $table->timestamp('email_verified_at')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
