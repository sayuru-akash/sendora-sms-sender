<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $duplicateRows = DB::table('import_rows')
            ->select('import_id', 'row_number', DB::raw('MIN(id) as keep_id'))
            ->groupBy('import_id', 'row_number')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateRows as $duplicateRow) {
            DB::table('import_rows')
                ->where('import_id', $duplicateRow->import_id)
                ->where('row_number', $duplicateRow->row_number)
                ->where('id', '<>', $duplicateRow->keep_id)
                ->delete();
        }

        Schema::table('import_rows', function (Blueprint $table) {
            $table->unique(['import_id', 'row_number'], 'import_rows_import_row_number_unique');
        });

        Schema::table('sms_messages', function (Blueprint $table) {
            $table->index(['campaign_recipient_id', 'status'], 'sms_messages_recipient_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sms_messages', function (Blueprint $table) {
            $table->dropIndex('sms_messages_recipient_status_index');
        });

        Schema::table('import_rows', function (Blueprint $table) {
            $table->dropUnique('import_rows_import_row_number_unique');
        });
    }
};
