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
        Schema::create('crawl_entries', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('domain');
            $table->string('name');
            $table->text('path');
            $table->string('status');
            $table->string('title');
            $table->string('type')->nullable();
            $table->string('description')->nullable();
            $table->string('favicon')->nullable();
            $table->string('image')->nullable();
            $table->string('keywords')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crawl_entries');
    }
};
