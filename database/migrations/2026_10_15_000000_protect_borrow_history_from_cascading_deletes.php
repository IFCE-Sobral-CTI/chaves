<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->replaceConstraints(restrict: true);
    }

    public function down(): void
    {
        $this->replaceConstraints(restrict: false);
    }

    private function replaceConstraints(bool $restrict): void
    {
        foreach ([
            'borrow_key' => ['key_id' => ['keys', 'cascade']],
            'key_received' => ['key_id' => ['keys', 'cascade']],
            'borrows' => [
                'employee_id' => ['employees', 'cascade'],
                'user_id' => ['users', 'cascade'],
                'received_by' => ['users', 'set null'],
            ],
            'receiveds' => ['user_id' => ['users', 'cascade']],
        ] as $name => $references) {
            Schema::table($name, function (Blueprint $table) use ($name, $references, $restrict) {
                foreach ($references as $column => [$parent, $onDelete]) {
                    $original = $name.'_'.$column.'_foreign';
                    $protected = $name.'_'.$column.'_history_foreign';
                    // Add the replacement before dropping the existing FK; never disable checks.
                    $table->foreign($column, $restrict ? $protected : $original)
                        ->references('id')->on($parent)->cascadeOnUpdate()
                        ->onDelete($restrict ? 'restrict' : $onDelete);
                    $table->dropForeign($restrict ? $original : $protected);
                }
            });
        }
    }
};
