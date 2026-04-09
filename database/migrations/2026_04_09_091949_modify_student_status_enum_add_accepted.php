<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE students DROP CONSTRAINT IF EXISTS students_status_check;");
        DB::statement("ALTER TABLE students ADD CONSTRAINT students_status_check CHECK (status::text = ANY (ARRAY['pending'::character varying, 'accepted'::character varying, 'in_progress'::character varying, 'completed'::character varying]::text[]));");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE students DROP CONSTRAINT IF EXISTS students_status_check;");
        DB::statement("ALTER TABLE students ADD CONSTRAINT students_status_check CHECK (status::text = ANY (ARRAY['pending'::character varying, 'in_progress'::character varying, 'completed'::character varying]::text[]));");
    }
};
