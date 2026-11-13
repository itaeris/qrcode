<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qr_links', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        Schema::table('short_links', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        $owner = DB::table('users')->orderBy('id')->value('id');

        if ($owner) {
            DB::table('qr_links')->whereNull('user_id')->update(['user_id' => $owner]);
            DB::table('short_links')->whereNull('user_id')->update(['user_id' => $owner]);
        }
    }

    public function down(): void
    {
        Schema::table('short_links', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('qr_links', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
