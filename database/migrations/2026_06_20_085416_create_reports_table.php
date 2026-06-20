<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
{
    Schema::create('reports', function (Blueprint $table) {
        $table->id();
        $table->foreignId('volunteer_id')->constrained('volunteers')->onDelete('cascade'); 
        
        $table->enum('report_type', ['campaign', 'organization']); 
        
        $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->onDelete('cascade');
        $table->foreignId('organization_id')->nullable()->constrained('organizations')->onDelete('cascade');
        
        $table->string('reason');       
        $table->text('description');    
        $table->string('evidence')->nullable(); 
        
        $table->string('status')->default('pending'); 
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
