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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('guard_name', 10)->default('web');
            $table->string('name', 64);
            $table->string('password');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('restaurant_id')->nullable();
            $table->double('credit', 19, 2)->default(0);
            $table->double('initial_credit', 19, 2)->default(0);
            $table->string('nickname', 64)->nullable();
            $table->string('contact_number', 64)->nullable();
            $table->string('avatar')->nullable();
            $table->tinyInteger('first_time_login')->default('1')->comment('1=yes, 0=no');
            $table->string('fe_lang', 10)->default('en');
            $table->datetime('last_time_login')->nullable();
            $table->string('last_ip_login', 64)->nullable();
            $table->boolean('is_two_factor_enabled')->default(false);
            $table->text('two_factor_secret')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->smallInteger('role_id')->nullable();
            $table->text('fcm_token')->nullable();
            $table->string('status', 64);
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
