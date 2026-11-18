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
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('checkout_photo_path')->nullable()->after('checkout_time');
            $table->decimal('checkout_lat', 10, 7)->nullable()->after('checkout_photo_path');
            $table->decimal('checkout_lng', 10, 7)->nullable()->after('checkout_lat');
            $table->boolean('is_checkout_within_radius')->nullable()->after('checkout_lng');
            $table->integer('checkout_accuracy_meters')->nullable()->after('is_checkout_within_radius');
            $table->boolean('is_checkout_mock_detected')->default(false)->after('checkout_accuracy_meters');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'checkout_photo_path',
                'checkout_lat',
                'checkout_lng',
                'is_checkout_within_radius',
                'checkout_accuracy_meters',
                'is_checkout_mock_detected',
            ]);
        });
    }
};
