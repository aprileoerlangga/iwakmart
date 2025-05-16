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
            $table->string('nama');
            $table->string('slug')->unique();
            $table->text('deskripsi')->nullable();
            $table->decimal('harga', 12, 2);
            $table->integer('stok')->default(0);
            $table->foreignId('kategori_id')->constrained('categories')->onDelete('restrict');
            $table->foreignId('penjual_id')->constrained('users')->onDelete('restrict');
            $table->json('gambar')->nullable();
            $table->decimal('berat', 8, 2)->nullable(); // dalam kilogram
            $table->enum('jenis_ikan', ['segar', 'beku', 'olahan', 'hidup'])->default('segar');
            $table->string('spesies_ikan')->nullable();
            //$table->date('tanggal_tangkap')->nullable();
            //$table->string('asal_ikan')->nullable();
            $table->decimal('rating_rata', 2, 1)->default(0);
            $table->unsignedInteger('jumlah_ulasan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->boolean('unggulan')->default(false);
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