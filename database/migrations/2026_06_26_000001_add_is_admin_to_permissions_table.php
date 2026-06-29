<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Substitui o controle de admin baseado na string `description` por uma flag
     * booleana estável. Faz backfill das permissões já existentes cuja descrição
     * é "Administrador".
     */
    public function up(): void
    {
        if (! Schema::hasColumn('permissions', 'is_admin')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->boolean('is_admin')->default(false)->after('description');
            });
        }

        DB::table('permissions')->where('description', 'Administrador')->update(['is_admin' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('permissions', 'is_admin')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->dropColumn('is_admin');
            });
        }
    }
};
