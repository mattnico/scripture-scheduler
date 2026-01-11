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
        Schema::create('required_chapters', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('curriculum_id')->constrained('curricula')->onDelete('cascade');
            $table->tinyInteger('volume_id'); // 1=OT, 2=NT, 3=BoM, 4=D&C, 5=PGP
            $table->string('book_title');
            $table->integer('chapter_start');
            $table->integer('chapter_end')->nullable(); // null = single chapter
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('required_chapters');
    }
};
