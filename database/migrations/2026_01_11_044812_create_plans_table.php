<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('enrollment_id')->nullable()->constrained()->nullOnDelete();
            
            $table->date('start_date');
            $table->date('end_date');
            $table->string('scheduling_method')->default('verse');
            $table->string('beginning_verse')->nullable();
            $table->json('volumes');
            
            $table->integer('total_words')->default(0);
            $table->integer('words_per_day')->default(0);
            $table->integer('total_days')->default(0);
            
            $table->json('schedule');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
