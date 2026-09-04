<?php

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\User;

test('poor attendance table includes students below 75% and excludes students at or above it', function () {
    $classRoom = ClassRoom::factory()->create();

    $strugglingStudent = Student::factory()->create(['fullname' => 'Struggling Student']);
    $goodStudent = Student::factory()->create(['fullname' => 'Good Student']);
    $classRoom->students()->attach([$strugglingStudent->id, $goodStudent->id]);

    $presentSession = AttendanceSession::factory()->create(['class_room_id' => $classRoom->id]);
    $absentSession = AttendanceSession::factory()->create(['class_room_id' => $classRoom->id]);

    // Struggling student: present (100) + absent (0) => average 50, below 75.
    AttendanceRecord::factory()->create([
        'attendance_session_id' => $presentSession->id,
        'student_id' => $strugglingStudent->id,
        'status' => 'present',
    ]);
    AttendanceRecord::factory()->create([
        'attendance_session_id' => $absentSession->id,
        'student_id' => $strugglingStudent->id,
        'status' => 'absent',
    ]);

    // Good student: present both times => average 100, at/above 75.
    AttendanceRecord::factory()->create([
        'attendance_session_id' => $presentSession->id,
        'student_id' => $goodStudent->id,
        'status' => 'present',
    ]);
    AttendanceRecord::factory()->create([
        'attendance_session_id' => $absentSession->id,
        'student_id' => $goodStudent->id,
        'status' => 'present',
    ]);

    $response = $this->actingAs(User::factory()->create())->get(route('dashboard'));

    $response->assertOk();
    $response->assertViewHas('poorAttendanceStudents', function ($rows) {
        return $rows->pluck('fullname')->all() === ['Struggling Student'];
    });
});
