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
        Schema::create('backend_locales', function (Blueprint $table) {
            $table->id();
            $table->string('word', 100);
            $table->string('type')->nullable();
            $table->text('en')->nullable();
            $table->text('zh')->nullable();
            $table->text('bn')->nullable();
            $table->text('fil')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backend_locales');
    }
};
