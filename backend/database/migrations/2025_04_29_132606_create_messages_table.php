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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengirim_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('penerima_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('janji_temu_id')->nullable()->constrained('appointments')->onDelete('cascade');
            $table->foreignId('produk_id')->nullable()->constrained('products')->onDelete('set null');
            $table->text('isi');
            $table->timestamp('dibaca_pada')->nullable();
            $table->json('lampiran')->nullable();
            $table->enum('jenis', ['teks', 'gambar', 'lokasi'])->default('teks');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};