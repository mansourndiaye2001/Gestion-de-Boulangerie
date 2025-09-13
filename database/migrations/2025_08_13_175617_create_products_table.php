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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name'); 
            $table->text('description')->nullable(); 
            $table->decimal('price', 10, 2); 
            $table->string('image')->nullable(); 
            $table->integer('stock')->default(0); 
            $table->boolean('is_promotion')->default(false); 
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->date('promotion_start_date')->nullable()->after('is_promotion');
            $table->date('promotion_end_date')->nullable()->after('promotion_start_date');
            $table->decimal('promotion_price', 10, 2)->nullable()->after('promotion_end_date');
        
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
