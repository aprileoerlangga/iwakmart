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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('restrict');
            $table->string('nomor_pesanan')->unique();
            $table->enum('status', ['menunggu', 'dibayar', 'diproses', 'dikirim', 'selesai', 'dibatalkan'])->default('menunggu');
            $table->string('metode_pembayaran')->nullable();
            $table->enum('status_pembayaran', ['menunggu', 'dibayar', 'gagal'])->default('menunggu');
            $table->string('id_pembayaran')->nullable();
            $table->foreignId('alamat_id')->constrained('addresses')->onDelete('restrict');
            $table->string('metode_pengiriman')->nullable();
            $table->decimal('biaya_kirim', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('pajak', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};