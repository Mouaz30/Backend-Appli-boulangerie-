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
        Schema::create('produit_promotion', function (Blueprint $table) {
           $table->id();
           $table->foreignId('produit_id')->constrained()->onDelete('cascade');
           $table->foreignId('promotion_id')->constrained()->onDelete('cascade');
           $table->timestamps();
            
           $table->unique(['produit_id', 'promotion_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produit_promotion');
    }
};
