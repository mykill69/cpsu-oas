<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('survey_visits', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->enum('visit_type', ['PRELIMINARY', 'FIRST', 'SECOND', 'THIRD', 'FOURTH']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_visit_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('code');
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('has_parameters')->default(true);
            $table->timestamps();
        });

        Schema::create('parameters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('code');
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_compliance')->default(false);
            $table->timestamps();
        });

        Schema::create('colleges', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('dean')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_extension')->default(false);
            $table->timestamps();
        });

        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('college_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('code')->unique();
            $table->enum('accreditation_level', ['CANDIDATE', 'LEVEL1', 'LEVEL2', 'LEVEL3', 'LEVEL4'])->default('CANDIDATE');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('programs');
        Schema::dropIfExists('parameters');
        Schema::dropIfExists('areas');
        Schema::dropIfExists('colleges');
        Schema::dropIfExists('survey_visits');
    }
};