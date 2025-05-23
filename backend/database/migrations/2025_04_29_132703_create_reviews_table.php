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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('produk_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('item_pesanan_id')->constrained('order_items')->onDelete('cascade');
            $table->tinyInteger('rating')->unsigned(); // 1-5 bintang
            $table->text('komentar')->nullable();
            $table->json('gambar')->nullable();
            $table->timestamps();
            
            // Memastikan pengguna hanya dapat mengulas produk sekali per item pesanan
            $table->unique(['user_id', 'produk_id', 'item_pesanan_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};