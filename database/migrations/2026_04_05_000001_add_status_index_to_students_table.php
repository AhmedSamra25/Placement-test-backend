<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a database index to students.status.
     * This column is heavily queried (dashboard stats, student listing filters)
     * and must be indexed to prevent full-table scans at scale.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->index('status', 'students_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex('students_status_idx');
        });
    }
};
