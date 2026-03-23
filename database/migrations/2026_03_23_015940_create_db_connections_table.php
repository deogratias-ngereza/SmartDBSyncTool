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
        Schema::create('db_connections', function (Blueprint $table) {
            $table->string('id', 20)->primary();
            $table->string('project_id', 20);
            $table->string('name');
            $table->string('driver'); // mysql, pgsql, mariadb
            $table->string('host');
            $table->integer('port');
            $table->string('database');
            $table->string('username');
            $table->text('encrypted_password');
            $table->boolean('is_controller')->default(false);
            $table->string('current_version_id', 20)->nullable();
            $table->json('metadata')->nullable(); // tags, region, custom flags
            $table->softDeletes();
            $table->timestamps();

            // Foreign keys
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            
            // Indexes
            $table->index('project_id');
            $table->index('driver');
            $table->index('is_controller');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('db_connections');
    }
};
