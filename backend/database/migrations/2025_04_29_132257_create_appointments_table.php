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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penjual_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('pembeli_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('lokasi_penjual_id')->constrained('seller_locations')->onDelete('cascade');
            $table->dateTime('tanggal_janji');
            $table->enum('status', ['menunggu', 'dikonfirmasi', 'selesai', 'dibatalkan'])->default('menunggu');
            $table->string('tujuan')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};