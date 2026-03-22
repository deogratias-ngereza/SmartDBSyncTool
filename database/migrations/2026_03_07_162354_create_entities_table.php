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
        Schema::create('entities', function (Blueprint $table) {
            $table->string('id', 45)->primary();
            $table->string('code', 45)->nullable();
            $table->string('name', 45)->nullable();
            $table->string('full_name', 45)->nullable();
            $table->string('first_name', 45)->nullable();
            $table->string('last_name', 45)->nullable();
            $table->string('phone1', 45)->nullable();
            $table->string('phone2', 45)->nullable();
            $table->string('email1', 60)->nullable();
            $table->string('email2', 60)->nullable();
            $table->string('tin', 45)->nullable();
            $table->string('vrn', 45)->nullable();
            $table->string('region', 45)->nullable();
            $table->string('address', 120)->nullable();
            $table->string('entity_type', 45)->default('INDIVIDUAL');
            $table->string('status', 45)->default('PENDING');
            $table->dateTime('created_at')->default(now());
            $table->date('created_date')->nullable();
            $table->integer('created_by')->nullable();
            $table->dateTime('updated_at')->default(now());
            $table->date('updated_date')->nullable();
            $table->integer('updated_by')->nullable();
            $table->date('deleted_date')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->integer('deleted')->default(0);
            $table->dateTime('deleted_at')->nullable();
            $table->string('default_init_module', 45)->nullable();
            $table->integer('linked_uvdesk_id')->nullable();
            $table->integer('is_blocked')->default(0);
            $table->string('block_reason', 60)->nullable();
            $table->string('subscription_package_code', 45)->default('BASIC');
            $table->string('business_code', 45)->default('ANY');
            $table->date('subscription_exp_date')->nullable();
            $table->double('subscription_amt', 15, 2)->default(0);
            $table->string('subscription_currency', 45)->default('TZSH');
            $table->string('primary_color', 45)->nullable();
            $table->string('date_format', 45)->default('yyyy-MM-dd');
            $table->string('time_format', 45)->default('HH:mm:ss');
            $table->string('timezone', 45)->default('Africa/Nairobi');
            $table->integer('auto_detect_timezone')->default(1);
            $table->string('locale', 45)->nullable();
            $table->string('currency_id', 45)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entities');
    }
};
