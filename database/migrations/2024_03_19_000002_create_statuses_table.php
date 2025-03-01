<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type')->default('text'); // text, image, video
            $table->text('content')->nullable(); // For text status
            $table->string('media_url')->nullable(); // For media status
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        Schema::create('status_viewers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('status_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('viewed_at');
            $table->timestamps();
        });

        Schema::create('status_privacy', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('privacy_type', ['all', 'selected', 'except'])->default('all');
            $table->json('selected_users')->nullable(); // For 'selected' and 'except' types
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('status_viewers');
        Schema::dropIfExists('status_privacy');
        Schema::dropIfExists('statuses');
    }
};
