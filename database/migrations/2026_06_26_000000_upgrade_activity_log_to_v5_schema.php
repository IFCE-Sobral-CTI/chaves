<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('activity_log')) {
            return;
        }

        if (! Schema::hasColumn('activity_log', 'attribute_changes')) {
            Schema::table('activity_log', function (Blueprint $table) {
                $table->json('attribute_changes')->nullable()->after('causer_id');
            });
        }

        if (! Schema::hasColumn('activity_log', 'event')) {
            Schema::table('activity_log', function (Blueprint $table) {
                $table->string('event')->nullable()->after('description');
            });
        }

        if (Schema::hasColumn('activity_log', 'batch_uuid')) {
            $driver = Schema::getConnection()->getDriverName();

            if ($driver === 'sqlite') {
                Schema::dropColumns('activity_log', ['batch_uuid']);
            } else {
                Schema::table('activity_log', function (Blueprint $table) {
                    $table->dropColumn('batch_uuid');
                });
            }
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        $columns = Schema::getConnection()->getSchemaBuilder()->getColumnListing('activity_log');

        if (in_array('properties', $columns, true) && in_array('attribute_changes', $columns, true)) {
            DB::table('activity_log')
                ->where(function ($query) {
                    $query->whereNotNull('properties')
                        ->orWhereNotNull('attribute_changes');
                })
                ->orderBy('id')
                ->chunkById(200, function ($rows) {
                    foreach ($rows as $row) {
                        $properties = $row->properties ? json_decode($row->properties, true) : [];

                        if (! is_array($properties)) {
                            $properties = [];
                        }

                        $changes = array_intersect_key(
                            $properties,
                            array_flip(['attributes', 'old'])
                        );

                        $remaining = array_diff_key(
                            $properties,
                            array_flip(['attributes', 'old'])
                        );

                        DB::table('activity_log')->where('id', $row->id)->update([
                            'attribute_changes' => empty($changes) ? null : json_encode($changes),
                            'properties' => empty($remaining) ? null : json_encode($remaining),
                        ]);
                    }
                });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('activity_log')) {
            return;
        }

        if (Schema::hasColumn('activity_log', 'attribute_changes')) {
            $driver = Schema::getConnection()->getDriverName();

            if ($driver === 'sqlite') {
                Schema::dropColumns('activity_log', ['attribute_changes']);
            } else {
                Schema::table('activity_log', function (Blueprint $table) {
                    $table->dropColumn('attribute_changes');
                });
            }
        }

        if (! Schema::hasColumn('activity_log', 'batch_uuid')) {
            Schema::table('activity_log', function (Blueprint $table) {
                $table->uuid('batch_uuid')->nullable()->after('properties');
            });
        }
    }
};
