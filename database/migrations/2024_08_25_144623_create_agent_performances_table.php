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
        Schema::create('agent_performances', function (Blueprint $table) {
            $table->id();
            $table->string('agent_name')->nullable();
            $table->string('team')->nullable();
            $table->string('kpi')->nullable();
            $table->bigInteger('billed')->nullable();
            $table->bigInteger('retained')->nullable();
            $table->decimal('billed_retained_percentage', 5, 2)->nullable(); // Example: 100.00%
            $table->bigInteger('npd_contacts')->nullable();
            $table->bigInteger('npd_conversations')->nullable();
            $table->decimal('npd_contacts_npd_conversations_percentage', 5, 2)->nullable();
            $table->bigInteger('upgrades')->nullable();
            $table->bigInteger('downgrades')->nullable();
            $table->decimal('upgrades_downgrade_percentage', 5, 2)->nullable();
            $table->bigInteger('m1_sales')->nullable();
            $table->bigInteger('M1_active')->nullable();
            $table->decimal('M1_active_m1_sales_percentage', 5, 2)->nullable();
            $table->string('month_performance')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_performances');
    }
};
