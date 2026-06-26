<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClassRoomRequest;
use App\Http\Requests\UpdateClassRoomRequest;
use App\Models\ClassRoom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ClassRoomController extends Controller
{
    public function index(): View
    {
        $classRooms = ClassRoom::withCount('students')
            ->with('schedules')
            ->orderBy('name')
            ->get();

        return view('classes.index', compact('classRooms'));
    }

    public function create(): View
    {
        $schedulesByDay = collect();
        $enabledDays = [];

        return view('classes.create', compact('schedulesByDay', 'enabledDays'));
    }

    public function store(StoreClassRoomRequest $request): RedirectResponse
    {
        $classRoom = ClassRoom::create($request->safe()->only(['name', 'code', 'section', 'description']));

        $this->syncSchedules(
            $classRoom,
            $request->validated('schedule_days', []),
            $request->validated('schedule_start', []),
            $request->validated('schedule_end', []),
        );

        return redirect()->route('classes.index')->with('success', 'Class created successfully.');
    }

    public function show(ClassRoom $classRoom): View
    {
        $classRoom->load(['schedules', 'students']);

        return view('classes.show', compact('classRoom'));
    }

    public function edit(ClassRoom $classRoom): View
    {
        $classRoom->load('schedules');
        $schedulesByDay = $classRoom->schedules->keyBy('day_of_week');
        $enabledDays = $classRoom->schedules->pluck('day_of_week')->toArray();

        return view('classes.edit', compact('classRoom', 'schedulesByDay', 'enabledDays'));
    }

    public function update(UpdateClassRoomRequest $request, ClassRoom $classRoom): RedirectResponse
    {
        $classRoom->update($request->safe()->only(['name', 'code', 'section', 'description']));

        $classRoom->schedules()->delete();
        $this->syncSchedules(
            $classRoom,
            $request->validated('schedule_days', []),
            $request->validated('schedule_start', []),
            $request->validated('schedule_end', []),
        );

        return redirect()->route('classes.index')->with('success', 'Class updated successfully.');
    }

    public function destroy(ClassRoom $classRoom): RedirectResponse
    {
        $classRoom->delete();

        return redirect()->route('classes.index')->with('success', 'Class deleted successfully.');
    }

    /**
     * @param array<int, string> $days
     * @param array<string, string> $startTimes
     * @param array<string, string> $endTimes
     */
    private function syncSchedules(ClassRoom $classRoom, array $days, array $startTimes, array $endTimes): void
    {
        foreach ($days as $day) {
            $classRoom->schedules()->create([
                'day_of_week' => $day,
                'start_time' => $startTimes[$day] ?? null,
                'end_time' => $endTimes[$day] ?? null,
            ]);
        }
    }
}
