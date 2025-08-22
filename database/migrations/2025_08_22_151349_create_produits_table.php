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
        Schema::create('produits', function (Blueprint $table) {
        $table->id();
        $table->string('nom');
        $table->text('description')->nullable();
        $table->float('prix', 10, 2);
        $table->unsignedBigInteger('categorie_id'); 
        $table->integer('stock')->default(0);
        $table->string('image')->nullable();
        $table->json('allergenes')->nullable();
        $table->boolean('est_actif')->default(true);
        $table->timestamps();
    });
    }

    /** 
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produits');
    }
};
