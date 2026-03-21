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
        Schema::table('menu_items', function (Blueprint $table) {
            $table->enum('is_veg', ['Yes', 'No'])->after('status');
            $table->enum('contain_egg', ['Yes', 'No'])->after('is_veg');
            $table->enum('contain_dairy', ['Yes', 'No'])->after('contain_egg');
            $table->enum('contain_onion_garlic', ['Yes', 'No'])->after('contain_dairy');
            $table->enum('contain_chili', ['Yes', 'No'])->after('contain_onion_garlic');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropColumn([
                'is_veg',
                'contain_egg',
                'contain_dairy',
                'contain_onion_garlic',
                'contain_chili',
            ]);
        });
    }
};
