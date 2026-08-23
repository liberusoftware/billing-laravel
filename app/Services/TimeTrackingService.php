<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;

class TimeTrackingService
{
    /**
     * Start a timer for the user on the given task. A user may only have one
     * running timer at a time, so any existing running entry is stopped first.
     */
    public function start(Task $task, User $user): TimeEntry
    {
        TimeEntry::running()
            ->where('user_id', $user->id)
            ->get()
            ->each(fn (TimeEntry $entry) => $this->stop($entry));

        return TimeEntry::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'started_at' => now(),
            'ended_at' => null,
            'duration_seconds' => 0,
        ]);
    }

    /**
     * Stop a running timer and record its elapsed duration in seconds.
     */
    public function stop(TimeEntry $entry): TimeEntry
    {
        $endedAt = now();

        $entry->update([
            'ended_at' => $endedAt,
            'duration_seconds' => (int) $entry->started_at->diffInSeconds($endedAt, absolute: true),
        ]);

        return $entry;
    }
}
