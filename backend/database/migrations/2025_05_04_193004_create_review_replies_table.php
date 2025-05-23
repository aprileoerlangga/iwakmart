<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // Membuat tabel 'review_replies'
        Schema::create('review_replies', function (Blueprint $table) {
            $table->id(); // Kolom ID auto-increment

            // Foreign key ke tabel 'reviews'
            // unique() memastikan hanya ada 1 balasan per ulasan
            // onDelete('cascade') berarti balasan akan ikut terhapus jika ulasan induknya dihapus
            $table->foreignId('review_id')->unique()->constrained('reviews')->onDelete('cascade');

            // Foreign key ke tabel 'users' (penjual yang membalas)
            // onDelete('cascade') berarti balasan akan ikut terhapus jika user penjualnya dihapus
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Kolom untuk menyimpan isi balasan
            $table->text('comment'); // Atau bisa dinamakan 'komentar' jika ingin konsisten Bhs Indonesia

            $table->timestamps(); // Kolom created_at dan updated_at

            // Index untuk performa query (opsional tapi disarankan)
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        // Menghapus tabel jika migrasi di-rollback
        Schema::dropIfExists('review_replies');
    }
};