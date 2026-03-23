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
        Schema::create('sync_tasks', function (Blueprint $table) {
            $table->string('id', 20)->primary();
            $table->string('project_id', 20);
            $table->string('name');
            $table->string('task_type'); // raw_query|migration|controller_sync|full_sync
            $table->longText('up_query')->nullable();
            $table->longText('down_query')->nullable();
            $table->string('execution_mode')->default('async');
            $table->integer('total_targets')->default(0);
            $table->integer('success_count')->default(0);
            $table->integer('failure_count')->default(0);
            $table->string('status')->default('pending'); // pending|processing|partial_failure|completed|cancelled
            $table->string('batch_id')->nullable(); // Laravel Bus batch ID
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Foreign keys
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            
            // Indexes
            $table->index('project_id');
            $table->index('task_type');
            $table->index('status');
            $table->index('batch_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_tasks');
    }
};
