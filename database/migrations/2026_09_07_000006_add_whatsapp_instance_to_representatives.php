<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('representatives', function (Blueprint $table) {
            $table->string('whatsapp_instance')->nullable()->unique()->after('code');
            $table->string('whatsapp_phone')->nullable()->after('whatsapp_instance');
            $table->string('whatsapp_status', 30)->default('disconnected')->after('whatsapp_phone');
            $table->timestamp('whatsapp_connected_at')->nullable()->after('whatsapp_status');
        });
    }

    public function down(): void
    {
        Schema::table('representatives', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_instance',
                'whatsapp_phone',
                'whatsapp_status',
                'whatsapp_connected_at',
            ]);
        });
    }
};
