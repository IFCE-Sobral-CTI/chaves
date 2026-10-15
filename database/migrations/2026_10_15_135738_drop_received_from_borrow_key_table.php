<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('borrow_key', function (Blueprint $table) {
            $table->dropColumn('received');
        });
    }

    /**
     * Recreates the column with its default; removed legacy values cannot be restored.
     */
    public function down(): void
    {
        Schema::table('borrow_key', function (Blueprint $table) {
            $table->boolean('received')->default(false);
        });
    }
};
