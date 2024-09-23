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
        Schema::create('retention_at_frontline', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('account');
            $table->string('channel');
            $table->integer('contact_bill_cycle')->nullable();
            $table->string('agent_name');
            $table->date('date')->nullable();
            $table->bigInteger('contact_day')->nullable();
            $table->string('due_this_month')->nullable();
            $table->string('month_of_contact')->nullable();
            $table->string('week_of_contact')->nullable();
            $table->string('contact_month_status')->nullable();
            $table->string('contact_month_package')->nullable();
            $table->bigInteger('contact_month_package_price')->nullable();
            $table->bigInteger('contact_month_account_sales')->nullable();
            $table->integer('current_bill_cycle')->nullable();
            $table->string('current_month_status')->nullable();
            $table->string('current_month_package')->nullable();
            $table->bigInteger('current_month_package_price')->nullable();
            $table->string('sales_retention')->nullable();
            $table->bigInteger('current_month_price_variance');
            $table->string('upgrade_downgrade')->nullable();
            $table->string('payment_status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retention_at_frontline');
    }
};
