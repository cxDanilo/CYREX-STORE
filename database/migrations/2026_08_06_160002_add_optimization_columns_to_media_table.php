<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->foreignId('folder_id')->nullable()->after('id')->constrained('media_folders')->nullOnDelete();
            $table->string('webp_path')->nullable()->after('path');
            $table->string('thumb_path')->nullable()->after('webp_path');
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropConstrainedForeignId('folder_id');
            $table->dropColumn(['webp_path', 'thumb_path']);
        });
    }
};
