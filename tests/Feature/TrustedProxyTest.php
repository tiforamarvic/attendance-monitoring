<?php

use App\Models\ClassRoom;
use App\Models\User;

test('a redirect uses https when the request arrives via a trusted proxy forwarding X-Forwarded-Proto: https', function () {
    // Terms are unconfigured by default (fresh migration seed), so exporting
    // triggers GradesController::export()'s redirect back to grades.index —
    // the exact real-world path that produced the mixed-content warning.
    $classRoom = ClassRoom::factory()->create();

    $response = $this->actingAs(User::factory()->create())
        ->withHeaders(['X-Forwarded-Proto' => 'https'])
        ->get(route('grades.export', ['class_id' => $classRoom->id]));

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toStartWith('https://');
});
