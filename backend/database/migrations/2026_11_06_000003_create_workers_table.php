<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('hostname')->nullable();
            $table->string('status')->default('idle');
            $table->foreignId('current_job_id')->nullable()->constrained('jobs')->nullOnDelete();
            $table->json('queues')->default('[]');
            $table->unsignedInteger('processed_jobs')->default(0);
            $table->unsignedInteger('failed_jobs')->default(0);
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workers');
    }
};
