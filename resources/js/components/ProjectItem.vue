<script setup lang="ts">
import { computed, ref } from 'vue';
import TaskItem from '@/components/TaskItem.vue';
import type { Project } from '@/services/notdone';
import { useNotDoneStore } from '@/stores/useNotDoneStore';

const props = defineProps<{
    project: Project;
    isNew?: boolean;
}>();

const emit = defineEmits<{
    addTask: [projectId: string];
    projectCompleted: [projectId: string];
}>();

const store = useNotDoneStore();
const hovered = ref(false);
const completing = ref(false);
const done = ref(false);

const tasks = computed(() => store.tasksForProject(props.project.id));
const floatAnimations = ['nd-float-a', 'nd-float-b', 'nd-float-c', 'nd-float-d', 'nd-float-e', 'nd-float-f'];
const floatName = floatAnimations[Math.floor(Math.random() * floatAnimations.length)];
const floatDuration = `${4 + Math.random() * 3}s`;
const floatDelay = `${Math.random() * 3}s`;

const priorityStyles = computed(() => {
    const p = props.project.priority;
    const sizes: Record<number, { fontSize: string; fontWeight: string; dotSize: string }> = {
        5: { fontSize: '38px', fontWeight: '900', dotSize: '10px' },
        4: { fontSize: '32px', fontWeight: '900', dotSize: '8px' },
        3: { fontSize: '26px', fontWeight: '900', dotSize: '7px' },
        2: { fontSize: '22px', fontWeight: '900', dotSize: '6px' },
        1: { fontSize: '18px', fontWeight: '900', dotSize: '5px' },
    };
    return sizes[p] || sizes[3];
});

// Track how many tasks have visually finished their fade-out
const alreadyDoneCount = tasks.value.filter((t) => t.completed_at).length;
const visuallyDoneCount = ref(alreadyDoneCount);
const totalTasks = computed(() => tasks.value.length);

function onTaskCompleted() {
    visuallyDoneCount.value++;

    // When all tasks have visually faded out, fade the project title
    if (visuallyDoneCount.value >= totalTasks.value && totalTasks.value > 0 && !completing.value) {
        completing.value = true;

        // Let the title fade play out, then remove
        setTimeout(() => {
            done.value = true;
            store.completeProject(props.project.id);
            emit('projectCompleted', props.project.id);
        }, 2000);
    }
}
</script>

<template>
    <div
        class="project-item"
        :class="{ completing, done }"
        :style="{
            animationName: floatName,
            animationDuration: floatDuration,
            animationDelay: floatDelay,
        }"
        @mouseenter="hovered = true"
        @mouseleave="hovered = false"
    >
        <div class="project-header">
            <span
                class="project-name"
                :style="{
                    fontSize: priorityStyles.fontSize,
                    fontWeight: priorityStyles.fontWeight,
                }"
            >
                {{ project.name }}
            </span>
            <button
                v-if="hovered && !completing"
                class="add-task-btn"
                @click.stop="emit('addTask', project.id)"
            >
                +
            </button>
        </div>

        <div class="task-list">
            <TaskItem
                v-for="(task, i) in tasks"
                :key="task.id"
                :task="task"
                :index="i"
                :is-new="isNew"
                @completed="onTaskCompleted"
            />
        </div>
    </div>
</template>

<style scoped>
.project-item {
    animation-timing-function: ease-in-out;
    animation-iteration-count: infinite;
    animation-direction: alternate;
    margin-bottom: 28px;
    transition: opacity 1.8s ease, transform 1.8s ease;
}

.project-item.done {
    opacity: 0;
    transform: translateY(-18px);
    pointer-events: none;
}

.project-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
}

.project-name {
    font-family: var(--nd-font-display);
    font-weight: 900;
    color: var(--nd-white);
    text-transform: uppercase;
    letter-spacing: 2px;
    transition: color 2s ease;
}

.completing .project-name {
    color: var(--nd-green);
}

.add-task-btn {
    background: none;
    border: none;
    color: var(--nd-ghost);
    font-family: var(--nd-font-mono);
    font-size: 14px;
    cursor: pointer;
    padding: 0 4px;
    line-height: 1;
    transition: color 0.2s ease;
}

.add-task-btn:hover {
    color: var(--nd-red);
}

.task-list {
    padding-left: 2px;
}
</style>
