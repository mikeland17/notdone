<?php

it('returns generated tasks for valid input', function () {
    $this->postJson('/api/generate-tasks', [
        'project' => 'Build a landing page',
        'priority' => 3,
    ])->assertSuccessful()
        ->assertJsonStructure(['tasks'])
        ->assertJsonCount(5, 'tasks');
});

it('requires a project name', function () {
    $this->postJson('/api/generate-tasks', [
        'priority' => 3,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('project');
});

it('rejects project names over 255 characters', function () {
    $this->postJson('/api/generate-tasks', [
        'project' => str_repeat('a', 256),
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('project');
});

it('rejects invalid priority values', function (mixed $priority) {
    $this->postJson('/api/generate-tasks', [
        'project' => 'Test project',
        'priority' => $priority,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('priority');
})->with([
    'zero' => 0,
    'too high' => 6,
    'string' => 'high',
]);

it('defaults priority to 3 when not provided', function () {
    $this->postJson('/api/generate-tasks', [
        'project' => 'Test project',
    ])->assertSuccessful();
});

it('is rate limited', function () {
    for ($i = 0; $i < 10; $i++) {
        $this->postJson('/api/generate-tasks', [
            'project' => 'Test project',
        ])->assertSuccessful();
    }

    $this->postJson('/api/generate-tasks', [
        'project' => 'Test project',
    ])->assertTooManyRequests();
});
