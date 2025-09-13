<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commandes', function (Blueprint $table) {
            $table->id();
            $table->string('numero_commande')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('statut', ['en_attente', 'confirmee', 'en_preparation', 'prete', 'livree', 'annulee'])
                  ->default('en_attente');
            $table->decimal('montant_total', 10, 2);
            $table->enum('mode_paiement', ['especes', 'en_ligne'])->default('especes');
            $table->text('adresse_livraison');
            $table->string('telephone_livraison');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};