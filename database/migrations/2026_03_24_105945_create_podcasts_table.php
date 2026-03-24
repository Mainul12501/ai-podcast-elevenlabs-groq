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
        Schema::create('podcasts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('product_url');
            $table->unsignedTinyInteger('conversation_length');
            $table->string('voice_alex_id');
            $table->string('voice_alex_name');
            $table->string('voice_sarah_id');
            $table->string('voice_sarah_name');
            $table->json('dialogue');
            $table->string('audio_path');
            $table->string('audio_url');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('podcasts');
    }
};
