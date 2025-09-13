<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type'); 
            $table->string('notifiable_type'); 
            $table->unsignedBigInteger('notifiable_id'); // ID de l'utilisateur
            $table->text('donnees'); 
            $table->timestamp('lu_le')->nullable(); 
            $table->timestamp('cree_le')->useCurrent(); 
            $table->timestamp('modifie_le')->useCurrent()->useCurrentOnUpdate();
            
            $table->index(['notifiable_type', 'notifiable_id']);
            $table->index('cree_le');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('notifications');
    }
};