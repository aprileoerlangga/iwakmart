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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('judul');
            $table->text('isi');
            $table->enum('jenis', ['pesanan', 'pembayaran', 'janji_temu', 'chat', 'sistem'])->default('sistem');
            $table->timestamp('dibaca_pada')->nullable();
            $table->json('data')->nullable();
            $table->string('tautan')->nullable();
            $table->foreignId('pesanan_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->foreignId('janji_temu_id')->nullable()->constrained('appointments')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};