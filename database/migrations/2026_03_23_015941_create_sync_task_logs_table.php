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
        Schema::create('sync_task_logs', function (Blueprint $table) {
            $table->string('id', 20)->primary();
            $table->string('sync_task_id', 20);
            $table->string('db_connection_id', 20);
            $table->string('status')->default('pending'); // pending|processing|success|fail
            $table->longText('error_message')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->string('batch_id')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Foreign keys
            $table->foreign('sync_task_id')->references('id')->on('sync_tasks')->onDelete('cascade');
            $table->foreign('db_connection_id')->references('id')->on('db_connections')->onDelete('cascade');
            
            // Indexes
            $table->index('sync_task_id');
            $table->index('db_connection_id');
            $table->index('status');
            $table->index('batch_id');
            $table->index('executed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_task_logs');
    }
};
