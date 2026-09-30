<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // 'prelim' | 'midterm' | 'finals'
            $table->string('label');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });

        DB::table('terms')->insert([
            ['key' => 'prelim', 'label' => 'Prelim', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'midterm', 'label' => 'Midterm', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'finals', 'label' => 'Finals', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('terms');
    }
};
