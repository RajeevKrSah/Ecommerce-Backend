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
        Schema::table('product_images', function (Blueprint $table) {
            $table->string('cloudinary_public_id')->nullable()->after('image_url');
            $table->string('thumbnail_url')->nullable()->after('cloudinary_public_id');
            $table->integer('width')->nullable()->after('thumbnail_url');
            $table->integer('height')->nullable()->after('width');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->dropColumn(['cloudinary_public_id', 'thumbnail_url', 'width', 'height']);
        });
    }
};
