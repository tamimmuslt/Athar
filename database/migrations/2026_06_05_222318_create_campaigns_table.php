<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
       Schema::create('campaigns', function (Blueprint $table) {
    $table->id();
    $table->foreignId('organization_id')->constrained()->onDelete('cascade');
    $table->string('title');
    $table->text('about');
    $table->text('requirements')->nullable();
    $table->string('location'); // مثل: Aleppo, The Public Park
    $table->string('meeting_point')->nullable(); // نقطة الالتقاء الخريطة
    $table->double('latitude')->nullable();
    $table->double('longitude')->nullable();
    $table->date('date');
    $table->time('time');
    
    // العدادات الظاهرة بالواجهة
    $table->integer('volunteers_needed');
    $table->integer('volunteers_registered')->default(0);
    
    $table->string('image')->nullable();
    $table->enum('type', ['on-ground', 'remote'])->default('on-ground');
    $table->enum('status', ['pending', 'active', 'completed', 'cancelled'])->default('pending');
    $table->timestamps();
});
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};