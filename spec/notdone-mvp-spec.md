# NOT DONE — MVP Spec
## Claude Code Session Document

---

## Overview

Build the NotDone MVP as a Laravel 12 + Vue 3 app using the official Vue starter kit (Inertia.js). The frontend is fully functional via localStorage on day one. Backend persistence is stubbed via a service layer and wired in later. No fluff, no onboarding — just the brutalist UI and the core loop.

**Live domain:** notdone.io

---

## Tech Stack

- **Framework:** Laravel 13 with official Vue starter kit (Inertia.js + Vue 3)
- **CSS:** Tailwind CSS (comes with starter kit)
- **Auth:** Laravel Breeze (included in starter kit)
- **DB:** SQLite for local dev, Postgres for production
- **Fonts:** Big Shoulders Display (900 weight), IBM Plex Mono — load via Google Fonts in app layout
- **State:** Pinia for Vue store
- **No additional UI component libraries**

---

## Project Setup Steps

```bash
laravel new notdone
# Select: Vue starter kit, Breeze auth, SQLite, Pest
# Requires Laravel installer 13.x: composer global require laravel/installer

cd notdone
npm install
npm install pinia
```

Add to `resources/js/app.js`:
```js
import { createPinia } from 'pinia'
app.use(createPinia())
```

Add Google Fonts to `resources/views/app.blade.php` `<head>`:
```html
<link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@900&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
```

---

## Data Model

### localStorage Schema

```js
// notdone_projects — array of Project
{
  id: string,           // uuid
  name: string,
  priority: number,     // 1-5
  created_at: string,   // ISO
  completed_at: string|null
}

// notdone_tasks — array of Task
{
  id: string,           // uuid
  project_id: string,
  label: string,
  created_at: string,
  completed_at: string|null
}
```

### Database Schema (Laravel migrations — wire up later)

```php
// projects table
Schema::create('projects', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->unsignedTinyInteger('priority')->default(3); // 1-5
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();
});

// tasks table
Schema::create('tasks', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('project_id');
    $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
    $table->string('label');
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();
});
```

---

## Service Layer

Create `resources/js/services/notdone.js`. All Vue components import ONLY from here — never touch localStorage or fetch directly.

```js
import { v4 as uuid } from 'uuid'

const PROJECTS_KEY = 'notdone_projects'
const TASKS_KEY = 'notdone_tasks'

const load = (key) => JSON.parse(localStorage.getItem(key) || '[]')
const save = (key, data) => localStorage.setItem(key, JSON.stringify(data))

export const NotDoneService = {

  // --- Projects ---
  getProjects() {
    return load(PROJECTS_KEY)
  },
  createProject({ name, priority = 3 }) {
    const projects = load(PROJECTS_KEY)
    const project = { id: uuid(), name, priority, created_at: new Date().toISOString(), completed_at: null }
    save(PROJECTS_KEY, [...projects, project])
    return project
  },
  completeProject(id) {
    const projects = load(PROJECTS_KEY)
    save(PROJECTS_KEY, projects.map(p => p.id === id ? { ...p, completed_at: new Date().toISOString() } : p))
  },
  deleteProject(id) {
    save(PROJECTS_KEY, load(PROJECTS_KEY).filter(p => p.id !== id))
    save(TASKS_KEY, load(TASKS_KEY).filter(t => t.project_id !== id))
  },

  // --- Tasks ---
  getTasksForProject(project_id) {
    return load(TASKS_KEY).filter(t => t.project_id === project_id)
  },
  createTask({ project_id, label }) {
    const tasks = load(TASKS_KEY)
    const task = { id: uuid(), project_id, label, created_at: new Date().toISOString(), completed_at: null }
    save(TASKS_KEY, [...tasks, task])
    return task
  },
  completeTask(id) {
    const tasks = load(TASKS_KEY)
    save(TASKS_KEY, tasks.map(t => t.id === id ? { ...t, completed_at: new Date().toISOString() } : t))
  },
  deleteTask(id) {
    save(TASKS_KEY, load(TASKS_KEY).filter(t => t.id !== id))
  },

  // --- STUBS (wire to API when backend is ready) ---
  // async syncToServer(userId) { return api.post('/sync', { projects, tasks }) },
  // async loadFromServer(userId) { return api.get('/sync') },
}
```

Install uuid: `npm install uuid`

---

## Routes

```php
// routes/web.php
Route::get('/', fn() => inertia('Home'))->name('home');

// Auth routes handled by Breeze automatically
// Future API routes go under /api prefix
```

---

## Vue Pages & Components

### File Structure

```
resources/js/
  pages/
    Home.vue          ← main app page
  components/
    ProjectList.vue   ← right side floating tasks panel
    ProjectItem.vue   ← single project with its tasks
    TaskItem.vue      ← single task row
    FocusCanvas.vue       ← full-screen focused input mode
    CursorBlink.vue       ← blinking red cursor component
  stores/
    useNotDoneStore.js
  services/
    notdone.js
    claude.js             ← Claude API task generation
```

---

## Pinia Store

`resources/js/stores/useNotDoneStore.js`

```js
import { defineStore } from 'pinia'
import { NotDoneService } from '../services/notdone'

export const useNotDoneStore = defineStore('notdone', {
  state: () => ({
    projects: [],
    tasks: [],
  }),
  getters: {
    activeProjects: (state) => state.projects.filter(p => !p.completed_at)
      .sort((a, b) => b.priority - a.priority),
    completedProjects: (state) => state.projects.filter(p => p.completed_at),
    tasksForProject: (state) => (project_id) =>
      state.tasks.filter(t => t.project_id === project_id),
    activeTasksForProject: (state) => (project_id) =>
      state.tasks.filter(t => t.project_id === project_id && !t.completed_at),
  },
  actions: {
    load() {
      this.projects = NotDoneService.getProjects()
      this.tasks = NotDoneService.getTasksForProject ? 
        JSON.parse(localStorage.getItem('notdone_tasks') || '[]') : []
    },
    addProject(data) {
      const p = NotDoneService.createProject(data)
      this.projects.push(p)
    },
    completeProject(id) {
      NotDoneService.completeProject(id)
      const p = this.projects.find(p => p.id === id)
      if (p) p.completed_at = new Date().toISOString()
    },
    addTask(data) {
      const t = NotDoneService.createTask(data)
      this.tasks.push(t)
    },
    completeTask(id) {
      NotDoneService.completeTask(id)
      const t = this.tasks.find(t => t.id === id)
      if (t) t.completed_at = new Date().toISOString()
    },
  }
})
```

---

## Home.vue — Mode State

Home.vue owns a single `mode` ref that drives everything. No modals, no overlays — the entire screen transitions between modes.

```js
const mode = ref('dashboard') 
// modes: 'dashboard' | 'add-project-name' | 'add-project-priority' | 'generating' | 'add-task'
const focusContext = ref(null) // holds { projectId } when in add-task mode
```

`<Dashboard />` and `<FocusCanvas />` are both always in the DOM. They transition via opacity + transform. Only one is "active" at a time.

```vue
<template>
  <div class="nd-root">
    <Dashboard :class="{ faded: mode !== 'dashboard' }" @add-project="enterAddProject" />
    <FocusCanvas :mode="mode" :context="focusContext" @done="returnToDashboard" />
  </div>
</template>
```

```css
.nd-root { position: relative; width: 100vw; height: 100vh; background: #080808; overflow: hidden; }
```

---

## Canvas Flow — Add Project

### Step 1: Trigger
User clicks "+ add project" in the bottom-left of the dashboard.

Dashboard fades out:
```css
transition: opacity 0.5s ease, transform 0.5s ease;
opacity: 0;
transform: translateY(-8px);
pointer-events: none;
```

### Step 2: Name Input (mode = 'add-project-name')
FocusCanvas fades in: pure #080808, nothing else on screen except:
- A single `<input>` — invisible, auto-focused, just a text cursor
- The cursor renders as a blinking red `|` — achieved by styling the caret:
```css
input {
  background: transparent;
  border: none;
  outline: none;
  color: #ffffff;
  font-family: 'IBM Plex Mono', monospace;
  font-size: 22px;
  caret-color: #ff1a1a;
  width: 100%;
  max-width: 600px;
}
```
- Ghost placeholder text: `"what are you not doing..."` in `#333`
- No labels. No buttons. No chrome.
- Escape → cancels, returns to dashboard

### Step 3: Priority Input (mode = 'add-project-priority')
User hits Enter on project name. Name fades up and out.

Five dots appear, centered:
```
○ ○ ● ○ ○
```
- Keyboard 1–5 to select immediately, or click a dot
- Selected dot fills red `#ff1a1a`, others remain hollow `#333`
- Small mono label beneath: `"priority — how badly are you not doing this"`
- No confirm button — selecting a priority auto-advances

### Step 4: Generating (mode = 'generating')
Priority selection fades out.

Screen shows only:
```
generating tasks_
```
In IBM Plex Mono, small, centered, `#555`. The underscore blinks red.

Claude API call happens here — see `claude.js` below.

### Step 5: Return to Dashboard
Tasks arrive from API. FocusCanvas fades out.
Dashboard fades back in. New project and its tasks appear already in the list, floating in from the right with a staggered entrance:
```css
/* each new task item */
animation: slideInRight 0.4s ease forwards;
animation-delay: calc(var(--i) * 0.08s);

@keyframes slideInRight {
  from { opacity: 0; transform: translateX(20px); }
  to   { opacity: 1; transform: translateX(0); }
}
```

---

## Canvas Flow — Add Task to Existing Project

User clicks a `+` that appears on hover next to a project name.

Same canvas transition — dashboard fades, FocusCanvas enters with `mode = 'add-task'` and `focusContext = { projectId }`.

Screen shows:
- Project name in dim `#333` at top left as context
- Single input, same style as above
- Placeholder: `"add a task..."`
- Enter submits task, input clears, stays in add-task mode for rapid entry
- Escape → returns to dashboard

No AI generation for individual tasks — those are manual.

---

## claude.js — Task Generation Service

```js
// resources/js/services/claude.js

export async function generateTasks(projectName, priority) {
  const response = await fetch('/api/generate-tasks', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ project: projectName, priority })
  })
  const data = await response.json()
  return data.tasks // array of strings
}
```

Laravel endpoint (stub for now, wire to Anthropic API):

```php
// routes/api.php
Route::post('/generate-tasks', function (Request $request) {
  $project = $request->input('project');
  $priority = $request->input('priority', 3);

  // STUB — replace with real Anthropic API call
  // Use claude-sonnet-4-5 model
  // Prompt: "You are a brutally honest productivity assistant.
  //   Given this project: '{$project}' (priority {$priority}/5),
  //   generate 3-5 concrete, specific, actionable tasks.
  //   Return ONLY a JSON array of short task strings. No fluff."
  
  return response()->json([
    'tasks' => [
      'Example task one',
      'Example task two', 
      'Example task three',
    ]
  ]);
});
```

Wire the real Anthropic call in a follow-up session using `claude-sonnet-4-6` model and `max_tokens: 300`. Response should be a raw JSON array — parse and return.

---

---

## Animation Spec

### Floating (idle state)
Each ProjectItem gets a unique float animation via inline style with randomised:
- `animation-duration`: random between 4s–7s
- `animation-delay`: random between 0s–3s
- Direction: `alternate` — drifts up then back

```css
@keyframes float {
  0% { transform: translateY(0px); }
  100% { transform: translateY(-12px); }
}
```

### Completing a Task
1. Click task → dot transitions from `#ff1a1a` to `#22cc66` (CSS transition 1.8s)
2. Task label fades to 40% opacity
3. After 1.8s → task slides up and fades out (`opacity: 0, transform: translateY(-16px)`, transition 0.6s)
4. Remove from DOM after transition ends
5. If all tasks in project complete → trigger project completion animation

### Completing a Project
1. All task dots green
2. Project name color transitions to `#22cc66`
3. After 2s → entire project block fades out and slides up
4. Remove from DOM
5. Update remaining count in left panel
6. If all projects done → red dot on NOT DONE transitions to green, pulse stops

### Red dot pulse (idle)
```css
@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.4; }
}
animation: pulse 2.4s ease-in-out infinite;
```

---

## Dashboard.vue Layout

The page is split into two zones:

**Left zone (60% width):** Big static branding. NOT DONE in Big Shoulders Display 900, massive. Red pulsing dot after the D. Muted count line below ("5 remaining"). Huge whitespace. `+ add project` link bottom left — IBM Plex Mono, 11px, `#333`, no button chrome. Hovering turns it `#ff1a1a`.

**Right zone (40% width):** ProjectList component. Projects sorted by priority descending. Each project floats independently. Priority drives visual weight:

```
Priority 5 → project name 18px, bold, dot 10px
Priority 4 → project name 15px, bold, dot 8px
Priority 3 → project name 13px, medium, dot 7px
Priority 2 → project name 12px, normal, dot 6px
Priority 1 → project name 11px, normal, dot 5px
```

**Background:** #080808. All text white or #e0e0e0. Inactive/sub text #555.

---

## Responsive

Desktop only for MVP. Min-width 900px. No mobile layout — call it out on the page: "Built for your desktop. Mobile coming." in small mono text bottom right.

---

## What Is NOT in MVP

- No weekly summary view (add later)
- No shame/pride score (add later)
- No voice/ping system (daemon work)
- No user settings
- No drag to reorder
- No due dates
- Backend auth exists (Breeze) but sync is not wired — login just saves your email for later

---

## Auth Stub

Show a "Save your data" CTA in the bottom left corner of the Home page for unauthenticated users. On click → redirect to /register. After registration → show "Syncing soon" message. Don't actually sync yet — just store user_id in localStorage for future use.

```js
// in service layer, stub:
// if (auth.user) await NotDoneService.syncToServer(auth.user.id)
```

---

## CSS Global Variables

Add to `resources/css/app.css`:

```css
:root {
  --nd-bg: #080808;
  --nd-white: #ffffff;
  --nd-muted: #e0e0e0;
  --nd-dim: #555555;
  --nd-red: #ff1a1a;
  --nd-green: #22cc66;
  --nd-font-display: 'Big Shoulders Display', Impact, sans-serif;
  --nd-font-mono: 'IBM Plex Mono', monospace;
}

body {
  background: var(--nd-bg);
  color: var(--nd-white);
  font-family: var(--nd-font-mono);
}
```

---

## Dev Commands

```bash
php artisan serve
npm run dev
# visits http://localhost:8000
```

---

## Definition of Done for This Session

- [ ] Laravel 13 + Vue starter kit running
- [ ] Dashboard.vue renders two-zone layout with correct fonts and colors
- [ ] Clicking "+ add project" fades dashboard, enters FocusCanvas name input mode
- [ ] Red blinking caret on name input, placeholder in #333, Escape cancels
- [ ] Enter on name → name fades, priority dot selector appears, keyboard 1–5 selects
- [ ] Selecting priority → "generating tasks_" screen, Claude API stub called
- [ ] Tasks returned, dashboard fades back in, new project slides in from right
- [ ] Clicking "+" on project hover → FocusCanvas add-task mode, rapid entry, Escape exits
- [ ] Tasks complete with animation (dot goes green, fades out)
- [ ] Projects complete with animation when all tasks done
- [ ] Red dot on NOT DONE goes green when everything is done
- [ ] Remaining count updates live
- [ ] All data persists in localStorage across page refresh
- [ ] Auth routes exist but sync is stubbed
- [ ] Mobile shows "desktop only" message
