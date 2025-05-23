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
        Schema::create('seller_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('nama_usaha');
            $table->text('deskripsi')->nullable();
            $table->text('alamat_lengkap');
            $table->string('provinsi');
            $table->string('kota');
            $table->string('kecamatan');
            $table->string('kode_pos');
            $table->boolean('aktif')->default(true);
            $table->json('jam_operasional')->nullable();
            $table->string('telepon');
            $table->json('foto')->nullable();
            $table->enum('jenis_penjual', ['nelayan', 'pembudidaya', 'grosir', 'ritel'])->default('ritel');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seller_locations');
    }
};