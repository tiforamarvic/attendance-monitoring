<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ClassRoom extends Model
{
    /** @use HasFactory<\Database\Factories\ClassRoomFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'section',
        'description',
    ];

    public function schedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class);
    }

    public function attendanceSessions(): HasMany
    {
        return $this->hasMany(AttendanceSession::class);
    }
}
