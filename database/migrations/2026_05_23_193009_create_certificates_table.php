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
    Schema::create('certificates', function (Blueprint $table) {
        $table->id();
        
        $table->foreignId('volunteer_id')->constrained('volunteers')->onDelete('cascade');
        
        $table->string('campaign_name');     
        $table->string('organization_name'); 
        $table->date('date');                
        
        $table->string('certificate_file'); 
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
