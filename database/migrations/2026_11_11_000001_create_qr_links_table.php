<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_links', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->string('secret', 80);
            $table->string('type', 40);
            $table->string('title', 160);
            $table->string('kind', 20);
            $table->json('payload');
            $table->json('style');
            $table->json('presentation');
            $table->text('target_url')->nullable();
            $table->text('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_mime')->nullable();
            $table->text('logo_path')->nullable();
            $table->unsignedInteger('scans')->default(0);
            $table->timestamps();
        });

        Schema::create('qr_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qr_link_id')->constrained()->cascadeOnDelete();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qr_link_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('qr_scans');
        Schema::dropIfExists('qr_links');
    }
};
