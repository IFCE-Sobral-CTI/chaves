<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('borrows', function (Blueprint $table) {
            $table->foreignId('received_by')
                ->nullable()
                ->after('devolution')
                ->constrained('users')
                ->onUpdate('cascade')
                ->onDelete('set null');
            $table->string('returned_by')->nullable()->after('received_by');
        });
    }

    public function down(): void
    {
        Schema::table('borrows', function (Blueprint $table) {
            $table->dropForeign(['received_by']);
            $table->dropColumn(['received_by', 'returned_by']);
        });
    }
};
