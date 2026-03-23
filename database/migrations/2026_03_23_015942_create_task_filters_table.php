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
        Schema::create('task_filters', function (Blueprint $table) {
            $table->string('id', 20)->primary();
            $table->string('sync_task_id', 20);
            $table->string('filter_type'); // id_list|exclusion|property|sql
            $table->json('filter_data'); // configuration depends on filter_type
            $table->softDeletes();
            $table->timestamps();

            // Foreign keys
            $table->foreign('sync_task_id')->references('id')->on('sync_tasks')->onDelete('cascade');
            
            // Indexes
            $table->index('sync_task_id');
            $table->index('filter_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_filters');
    }
};
