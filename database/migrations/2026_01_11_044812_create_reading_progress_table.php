<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reading_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('plan_id')->constrained()->cascadeOnDelete();
            $table->date('reading_date');
            $table->boolean('completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->unique(['plan_id', 'reading_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reading_progress');
    }
};
