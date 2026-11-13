<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qr_link_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 16);
            $table->timestamps();

            $table->unique(['qr_link_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_shares');
    }
};
