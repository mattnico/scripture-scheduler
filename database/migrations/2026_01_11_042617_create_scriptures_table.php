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
        Schema::create('scriptures', function (Blueprint $table) {
            $table->id(); // verse_id from CSV
            $table->tinyInteger('volume_id')->index();
            $table->string('volume_title');
            $table->string('book_title');
            $table->integer('chapter');
            $table->integer('verse')->nullable();
            $table->string('verse_title'); // e.g., "Genesis 1:1"
            $table->integer('word_count');
            $table->text('scripture_text')->nullable();
            
            // Indexes for common queries
            $table->index(['book_title', 'chapter']);
            $table->index('verse_title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scriptures');
    }
};
