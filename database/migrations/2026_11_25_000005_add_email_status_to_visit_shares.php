<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Whether the invitation email actually went out, so QA can see it on the share. */
return new class extends Migration
{
    public function up()
    {
        Schema::table('visit_shares', function (Blueprint $table) {
            $table->timestamp('emailed_at')->nullable()->after('otp_sent_at');
            $table->string('email_error', 500)->nullable()->after('emailed_at');
        });
    }

    public function down()
    {
        Schema::table('visit_shares', fn (Blueprint $t) => $t->dropColumn(['emailed_at', 'email_error']));
    }
};
