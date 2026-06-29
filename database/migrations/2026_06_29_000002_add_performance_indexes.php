<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('borrows', function (Blueprint $table) {
            $table->index('devolution');
        });

        Schema::table('keys', function (Blueprint $table) {
            $table->index('number');
            $table->index('description');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('name');
            $table->index('email');
        });

        Schema::table('rules', function (Blueprint $table) {
            $table->index('control');
        });
    }

    public function down(): void
    {
        Schema::table('borrows', function (Blueprint $table) {
            $table->dropIndex(['devolution']);
        });

        Schema::table('keys', function (Blueprint $table) {
            $table->dropIndex(['number']);
            $table->dropIndex(['description']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropIndex(['email']);
        });

        Schema::table('rules', function (Blueprint $table) {
            $table->dropIndex(['control']);
        });
    }
};
