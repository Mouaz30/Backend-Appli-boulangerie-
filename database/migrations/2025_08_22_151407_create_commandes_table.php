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
        Schema::create('commandes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('utilisateur_id')->constrained('users')->onDelete('cascade');
            $table->decimal('total', 10, 2);
            $table->enum('statut', [
                'en_attente', 
                'en_preparation', 
                'prete', 
                'en_livraison', 
                'livree', 
                'annulee'
            ])->default('en_attente');
            $table->text('adresse_livraison');
            $table->enum('methode_paiement', ['a_la_livraison', 'en_ligne']);
            $table->dateTime('date_livraison')->nullable();
            $table->string('numero_suivi')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};
