<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // ---- School years: every survey visit belongs to one; past years stay browsable.
        Schema::create('school_years', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('start_year')->unique(); // 2026 => SY 2026–2027
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });

        // The SY that contains today (Philippine SY starts around June–August).
        $start = now()->month >= 6 ? now()->year : now()->year - 1;
        $syId = DB::table('school_years')->insertGetId(['start_year' => $start, 'is_current' => true, 'created_at' => now(), 'updated_at' => now()]);

        Schema::table('survey_visits', function (Blueprint $table) {
            $table->foreignId('school_year_id')->nullable()->after('id')->constrained()->restrictOnDelete();
        });
        DB::table('survey_visits')->update(['school_year_id' => $syId]);

        // Visit codes only need to be unique within a year now.
        Schema::table('survey_visits', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->unique(['school_year_id', 'code']);
        });

        // ---- Google sign-in: institutional accounts registered by QA; passwords become optional.
        DB::statement('ALTER TABLE users MODIFY password VARCHAR(255) NULL');
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_sub')->nullable()->unique()->after('email');
            $table->timestamp('last_login_at')->nullable();
        });

        // ---- Google Drive mirror.
        Schema::table('folders', function (Blueprint $table) {
            $table->string('drive_id')->nullable();
        });
        DB::statement('ALTER TABLE documents MODIFY path VARCHAR(255) NULL'); // null once the file lives only in Drive
        Schema::table('documents', function (Blueprint $table) {
            $table->string('drive_id')->nullable();
            $table->timestamp('drive_synced_at')->nullable();
            $table->string('drive_error', 500)->nullable();
        });
        // Drive folder ids for the fixed levels: SY / college / visit / area / parameter.
        Schema::create('drive_nodes', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('drive_id');
            $table->timestamps();
        });

        Schema::create('app_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('app_settings');
        Schema::dropIfExists('drive_nodes');
        Schema::table('documents', fn (Blueprint $t) => $t->dropColumn(['drive_id', 'drive_synced_at', 'drive_error']));
        Schema::table('folders', fn (Blueprint $t) => $t->dropColumn('drive_id'));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['google_sub', 'last_login_at']));
        Schema::table('survey_visits', function (Blueprint $table) {
            $table->dropUnique(['school_year_id', 'code']);
            $table->unique('code');
            $table->dropConstrainedForeignId('school_year_id');
        });
        Schema::dropIfExists('school_years');
    }
};
