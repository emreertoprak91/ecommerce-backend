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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Basic info
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku', 100)->unique();
            $table->text('description')->nullable();

            // Pricing (stored in cents/kuruş)
            $table->unsignedBigInteger('price');
            $table->unsignedBigInteger('compare_price')->nullable();
            $table->unsignedBigInteger('cost')->nullable();

            // Inventory
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('low_stock_threshold')->default(10);

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable();

            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();

            // Metadata (for flexible attributes)
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for common queries
            $table->index(['is_active', 'published_at']);
            $table->index('sku');
            $table->index('price');
            $table->index('quantity');

            // Fulltext index only for MySQL
            if (config('database.default') === 'mysql') {
                $table->fullText(['name', 'description']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
