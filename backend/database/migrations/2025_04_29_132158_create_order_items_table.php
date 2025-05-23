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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesanan_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('produk_id')->constrained('products')->onDelete('restrict');

            // --- Perbaikan: Hapus ->after() ---
            $table->foreignId('penjual_id')->constrained('users')->onDelete('restrict'); // Tanpa ->after()
            $table->string('nama_produk'); // Tanpa ->after()
            // ----------------------------------

            $table->integer('jumlah');
            $table->decimal('harga', 12, 2); // Harga pada saat pembelian
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};