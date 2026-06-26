<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Scores: Present = 100, Late = 80, Absent = 0, Excused = 100
    // Grade per student = average of all session scores
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_session_id')
                ->constrained('attendance_sessions')
                ->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['present', 'late', 'absent', 'excused'])->default('absent');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['attendance_session_id', 'student_id']); // one record per student per session
            $table->index(['attendance_session_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
