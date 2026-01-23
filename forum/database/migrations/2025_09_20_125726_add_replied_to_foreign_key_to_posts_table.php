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
        Schema::table('posts', function (Blueprint $table) {
            $table->foreignId('replied_to_id')
                ->nullable()
                ->constrained('posts')
                ->cascadeOnDelete();
            $table->index('replied_to_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['replied_to_id']);
            $table->dropIndex(['replied_to_id']);
            $table->dropColumn('replied_to_id');
        });
    }
};
