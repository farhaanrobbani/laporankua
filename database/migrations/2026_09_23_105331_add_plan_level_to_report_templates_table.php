<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_templates', function (Blueprint $table) {
            $table->string('plan_level')->default('free')->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('report_templates', function (Blueprint $table) {
            $table->dropColumn('plan_level');
        });
    }
};
