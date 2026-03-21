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
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('restaurant_id');
            $table->unsignedBigInteger('menu_category_id');
            $table->string('name', 64);
            $table->string('meal_time', 64);
            $table->decimal('unit_price', 10, 2);
            $table->integer('available_quantity');
            $table->string('add_on', 64);
            $table->string('image', 100)->nullable();
            $table->string('remark', 100)->nullable();
            $table->string('status', 10);
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
