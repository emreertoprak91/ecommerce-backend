<?php

declare(strict_types=1);

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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('model_type', 100)->index();
            $table->unsignedBigInteger('model_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('event', 20)->index(); // created, updated, deleted
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Composite index for common queries
            $table->index(['model_type', 'model_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
