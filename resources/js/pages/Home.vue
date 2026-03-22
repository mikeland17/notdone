<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import FocusCanvas from '@/components/FocusCanvas.vue';
import type { FocusMode } from '@/components/FocusCanvas.vue';
import NotDoneDashboard from '@/components/NotDoneDashboard.vue';
import { generateTasks } from '@/services/claude';
import { useNotDoneStore } from '@/stores/useNotDoneStore';

const store = useNotDoneStore();
const page = usePage();

const mode = ref<FocusMode>('hidden');
const pendingProjectName = ref('');
const focusProjectName = ref('');
const focusProjectId = ref('');
const newProjectId = ref<string | null>(null);

function onGlobalKeydown(e: KeyboardEvent) {
    if (e.key === 'a' && mode.value === 'hidden' && !(e.target instanceof HTMLInputElement)) {
        e.preventDefault();
        // Small delay so the focus canvas input doesn't receive the 'a' keystroke
        requestAnimationFrame(() => {
            enterAddProject();
        });
    }
}

onMounted(() => {
    store.load();
    window.addEventListener('keydown', onGlobalKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', onGlobalKeydown);
});

function enterAddProject() {
    mode.value = 'add-project-name';
}

function onProjectName(name: string) {
    pendingProjectName.value = name;
    focusProjectName.value = name;
    mode.value = 'add-project-priority';
}

async function onPriority(priority: number) {
    mode.value = 'generating';

    const project = store.addProject({
        name: pendingProjectName.value,
        priority,
    });

    try {
        const taskLabels = await generateTasks(pendingProjectName.value, priority);
        for (const label of taskLabels) {
            store.addTask({ project_id: project.id, label });
        }
    } catch {
        // Stub fallback if API fails
        store.addTask({ project_id: project.id, label: 'Define scope' });
        store.addTask({ project_id: project.id, label: 'Break into steps' });
        store.addTask({ project_id: project.id, label: 'Start working' });
    }

    newProjectId.value = project.id;
    mode.value = 'hidden';

    // Clear "new" status after animations complete
    setTimeout(() => {
        newProjectId.value = null;
    }, 2000);
}

function enterAddTask(projectId: string) {
    const project = store.projects.find((p) => p.id === projectId);
    focusProjectId.value = projectId;
    focusProjectName.value = project?.name || '';
    mode.value = 'add-task';
}

function onTaskSubmit(label: string) {
    store.addTask({ project_id: focusProjectId.value, label });
}

function cancel() {
    mode.value = 'hidden';
    pendingProjectName.value = '';
}

const isAuthenticated = !!page.props.auth?.user;
</script>

<template>
    <Head title="NOT DONE" />

    <div class="nd-root">
        <!-- Dashboard -->
        <div class="nd-dashboard-wrapper" :class="{ faded: mode !== 'hidden' }">
            <NotDoneDashboard
                :new-project-id="newProjectId"
                @add-project="enterAddProject"
                @add-task="enterAddTask"
            />
        </div>

        <!-- Focus Canvas -->
        <FocusCanvas
            :mode="mode"
            :project-name="focusProjectName"
            @submit-project-name="onProjectName"
            @submit-priority="onPriority"
            @submit-task="onTaskSubmit"
            @cancel="cancel"
        />

        <!-- Auth CTA for unauthenticated users -->
        <div v-if="!isAuthenticated" class="nd-auth-cta">
            <a href="/register" class="nd-save-link">save your data →</a>
        </div>
    </div>
</template>

<style scoped>
.nd-root {
    position: relative;
    width: 100vw;
    height: 100vh;
    background: var(--nd-bg);
    overflow: hidden;
}

.nd-dashboard-wrapper {
    width: 100%;
    height: 100%;
    transition: opacity 0.6s ease, transform 0.6s ease;
}

.nd-dashboard-wrapper.faded {
    opacity: 0;
    transform: translateY(-4px);
    pointer-events: none;
}

.nd-auth-cta {
    position: fixed;
    bottom: 3.5rem;
    right: 3.5rem;
    z-index: 50;
}

.nd-save-link {
    font-family: var(--nd-font-mono);
    font-size: 10px;
    color: var(--nd-ghost);
    text-decoration: none;
    letter-spacing: 1px;
    transition: color 0.2s ease;
}

.nd-save-link:hover {
    color: var(--nd-red);
}

@media (max-width: 768px) {
    .nd-auth-cta {
        bottom: 2rem;
        right: 2rem;
    }
}
</style>
