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
        Schema::create('crm', function (Blueprint $table) {
            $table->id();
            $table->string('account_no')->nullable();
            $table->string('sub_name')->nullable();
            $table->bigInteger('sub_phone')->nullable();
            $table->string('channel_name')->nullable();
            $table->string('ticket_type_name')->nullable();
            $table->string('category_name')->nullable();
            $table->string('sub_category_name')->nullable();
            $table->string('description')->nullable();
            $table->string('shift_location')->nullable();
            $table->string('department_name')->nullable();
            $table->bigInteger('customer_no')->nullable();
            $table->bigInteger('customer_alternative_no')->nullable();
            $table->string('sub_status')->nullable();
            $table->string('current_pkg')->nullable();
            $table->string('last_pkg')->nullable();
            $table->integer('bill_cycle')->nullable();
            $table->string('sub_node')->nullable();
            $table->string('master_node')->nullable();
            $table->string('ticket_status_name')->nullable();
            $table->string('name')->nullable();
            $table->string('schedule_site_visit')->nullable();
            $table->date('installed_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm');
    }
};
