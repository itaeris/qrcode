<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qr_scans', function (Blueprint $table) {
            $table->string('city', 80)->nullable()->after('user_agent');
            $table->string('region', 80)->nullable()->after('city');
            $table->string('country', 80)->nullable()->after('region');
            $table->string('device', 80)->nullable()->after('country');
        });
    }

    public function down(): void
    {
        Schema::table('qr_scans', function (Blueprint $table) {
            $table->dropColumn(['city', 'region', 'country', 'device']);
        });
    }
};
