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
        Schema::create('movement', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('subs_account_no')->nullable();
            $table->bigInteger('subs_bill_cycle')->nullable();
            $table->date('subs_install_date')->nullable();
            $table->string('subs_current_status')->nullable();
            $table->string('subs_current_package')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movement');
    }
};
