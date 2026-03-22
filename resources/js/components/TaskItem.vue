<script setup lang="ts">
import { ref } from 'vue';
import type { Task } from '@/services/notdone';
import { useNotDoneStore } from '@/stores/useNotDoneStore';

const props = defineProps<{
    task: Task;
    index?: number;
    isNew?: boolean;
}>();

const emit = defineEmits<{
    completed: [taskId: string];
}>();

const store = useNotDoneStore();
const completing = ref(false);
const done = ref(!!props.task.completed_at);

function complete() {
    if (props.task.completed_at || completing.value || done.value) {
        return;
    }

    completing.value = true;

    // Wait for the red→green transition to play, THEN mark as done in store
    setTimeout(() => {
        done.value = true;
    }, 1800);

    // Persist after fade-out completes
    setTimeout(() => {
        store.completeTask(props.task.id);
        emit('completed', props.task.id);
    }, 3600);
}
</script>

<template>
    <div
        class="task-item"
        :class="{ completing, done }"
        :style="isNew ? { animation: `nd-slide-in-right 0.4s ease forwards`, animationDelay: `${(index || 0) * 0.08}s`, opacity: 0 } : {}"
        @click="complete"
    >
        <span class="task-dot" />
        <span class="task-label">{{ task.label }}</span>
    </div>
</template>

<style scoped>
.task-item {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    transition: opacity 1.8s ease, transform 1.8s ease;
    padding: 5px 0;
}

.task-item:hover .task-dot {
    transform: scale(1.4);
}

.task-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--nd-red);
    flex-shrink: 0;
    transition: background 1.8s ease, transform 0.2s ease;
}

.task-label {
    font-family: var(--nd-font-mono);
    font-size: 14px;
    color: var(--nd-muted);
    transition: color 1.8s ease, opacity 1.8s ease;
}

.completing .task-dot {
    background: var(--nd-green);
}

.completing .task-label {
    opacity: 0.6;
}

.done {
    opacity: 0;
    transform: translateY(-18px);
    pointer-events: none;
}
</style>
