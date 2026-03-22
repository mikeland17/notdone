<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/generate-tasks', function (Request $request) {
    $project = $request->input('project');
    $priority = $request->input('priority', 3);

    // STUB — replace with real Anthropic API call
    // Use claude-sonnet-4-6 model
    // Prompt: "You are a brutally honest productivity assistant.
    //   Given this project: '{$project}' (priority {$priority}/5),
    //   generate 3-5 concrete, specific, actionable tasks.
    //   Return ONLY a JSON array of short task strings. No fluff."

    return response()->json([
        'tasks' => [
            'Define the scope and constraints',
            'Break it into first actionable step',
            'Set up the environment or context',
            'Do the hard part you keep avoiding',
            'Ship it or mark it done',
        ],
    ]);
});
