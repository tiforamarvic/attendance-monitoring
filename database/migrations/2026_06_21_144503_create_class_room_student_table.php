<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Pivot: a student can be enrolled in multiple classes
    public function up(): void
    {
        Schema::create('class_room_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['class_room_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_room_student');
    }
};
