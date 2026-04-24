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
        Schema::create('kategori_layanans', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kategori'); // Nama
            $table->text('deskripsi'); // Deskripsi
            $table->date('tanggal_berlaku_harga'); // Tanggal berlaku harga
            $table->string('gambar'); // Path gambar
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kategori_layanans');
    }
};
