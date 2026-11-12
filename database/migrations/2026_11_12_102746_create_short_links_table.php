<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('short_links', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->string('title', 140);
            $table->text('url');
            $table->unsignedInteger('clicks')->default(0);
            $table->timestamps();
        });

        Schema::create('short_link_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('short_link_id')->constrained()->cascadeOnDelete();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('city', 80)->nullable();
            $table->string('region', 80)->nullable();
            $table->string('country', 80)->nullable();
            $table->string('device', 80)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('short_link_clicks');
        Schema::dropIfExists('short_links');
    }
};
