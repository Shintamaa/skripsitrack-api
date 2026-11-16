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
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stage_id')->constrained('thesis_stages')->cascadeOnDelete();
            $table->unsignedSmallInteger('version')->default(1);
            $table->string('file_path');
            $table->enum('file_type', ['image', 'pdf', 'doc'])->default('pdf');
            $table->text('note')->nullable();
            $table->enum('status', ['menunggu', 'perlu_revisi', 'disetujui'])->default('menunggu');
            $table->timestamps();

            $table->unique(['stage_id', 'version']); // tidak boleh ada versi ganda
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
