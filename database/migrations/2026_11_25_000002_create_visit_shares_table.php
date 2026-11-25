<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Read-only access to one college's binder for one survey visit, granted to an outside accreditor. */
return new class extends Migration
{
    public function up()
    {
        Schema::create('visit_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('college_id')->constrained()->cascadeOnDelete();
            $table->foreignId('survey_visit_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('token_hash', 64)->unique();   // sha256 of the link token; the raw token only travels in the email
            $table->string('otp_hash')->nullable();        // bcrypt of the current one-time code
            $table->timestamp('otp_expires_at')->nullable();
            $table->unsignedTinyInteger('otp_attempts')->default(0);
            $table->timestamp('otp_sent_at')->nullable();
            $table->dateTime('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('first_verified_at')->nullable();
            $table->timestamp('last_viewed_at')->nullable();
            $table->unsignedInteger('view_count')->default(0);
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('visit_shares');
    }
};
