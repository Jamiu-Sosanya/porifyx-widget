<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->enum('quality_grade', ['A', 'B', 'C', 'D'])->nullable()->after('temperature');
            $table->integer('engagement_score')->default(0)->after('quality_grade'); // 0-100
            $table->string('industry')->nullable()->after('company');
            $table->string('company_size')->nullable()->after('industry'); // small, medium, large, enterprise
            $table->timestamp('last_engagement_at')->nullable()->after('qualified_at');
            $table->integer('follow_up_priority')->default(0)->after('last_engagement_at'); // calculated priority score
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['quality_grade', 'engagement_score', 'industry', 'company_size', 'last_engagement_at', 'follow_up_priority']);
        });
    }
};
