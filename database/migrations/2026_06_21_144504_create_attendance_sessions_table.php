<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // One session = one day of attendance for a class (teacher picks the date manually)
    public function up(): void
    {
        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_room_id')->constrained()->cascadeOnDelete();
            $table->date('session_date');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['class_room_id', 'session_date']); // one session per class per day
            $table->index('class_room_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_sessions');
    }
};
