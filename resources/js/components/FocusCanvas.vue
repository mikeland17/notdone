<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue';
import CursorBlink from '@/components/CursorBlink.vue';

export type FocusMode = 'hidden' | 'add-project-name' | 'add-project-priority' | 'generating' | 'add-task';

const props = defineProps<{
    mode: FocusMode;
    projectName?: string;
}>();

const emit = defineEmits<{
    submitProjectName: [name: string];
    submitPriority: [priority: number];
    submitTask: [label: string];
    cancel: [];
}>();

const nameInput = ref('');
const taskInput = ref('');
const selectedPriority = ref(0);
const nameInputEl = ref<HTMLInputElement | null>(null);
const taskInputEl = ref<HTMLInputElement | null>(null);
const addedTasks = ref<string[]>([]);

const isVisible = ref(false);
const prioritySelectorEl = ref<HTMLElement | null>(null);

watch(
    () => props.mode,
    async (newMode) => {
        isVisible.value = newMode !== 'hidden';

        if (newMode === 'add-project-name') {
            nameInput.value = '';
        } else if (newMode === 'add-project-priority') {
            selectedPriority.value = 0;
        } else if (newMode === 'add-task') {
            taskInput.value = '';
            addedTasks.value = [];
        }

        await nextTick();

        if (newMode === 'add-project-name') {
            nameInputEl.value?.focus();
        } else if (newMode === 'add-project-priority') {
            prioritySelectorEl.value?.focus();
        } else if (newMode === 'add-task') {
            taskInputEl.value?.focus();
        }
    },
);

function onNameKeydown(e: KeyboardEvent) {
    if (e.key === 'Enter' && nameInput.value.trim()) {
        emit('submitProjectName', nameInput.value.trim());
    } else if (e.key === 'Escape') {
        emit('cancel');
    }
}

function selectPriority(p: number) {
    selectedPriority.value = Math.max(1, Math.min(5, p));
}

function confirmPriority() {
    if (selectedPriority.value >= 1) {
        emit('submitPriority', selectedPriority.value);
    }
}

function onPriorityKeydown(e: KeyboardEvent) {
    const num = parseInt(e.key);
    if (num >= 1 && num <= 5) {
        selectPriority(num);
    } else if (e.key === 'ArrowRight' || e.key === 'ArrowUp') {
        e.preventDefault();
        selectPriority((selectedPriority.value || 0) + 1);
    } else if (e.key === 'ArrowLeft' || e.key === 'ArrowDown') {
        e.preventDefault();
        selectPriority((selectedPriority.value || 2) - 1);
    } else if (e.key === 'Enter') {
        e.preventDefault();
        confirmPriority();
    } else if (e.key === 'Escape') {
        emit('cancel');
    }
}

const priorityPreviewSize = computed(() => {
    const sizes: Record<number, string> = {
        1: '36px',
        2: '48px',
        3: '64px',
        4: '82px',
        5: '100px',
    };
    return sizes[selectedPriority.value] || '28px';
});

function onTaskKeydown(e: KeyboardEvent) {
    if (e.key === 'Enter' && taskInput.value.trim()) {
        addedTasks.value.push(taskInput.value.trim());
        emit('submitTask', taskInput.value.trim());
        taskInput.value = '';
    } else if (e.key === 'Escape') {
        emit('cancel');
    }
}
</script>

<template>
    <div
        class="focus-canvas"
        :class="{ visible: isVisible }"
        tabindex="-1"
    >
        <div class="focus-inner">
            <!-- Add Project Name -->
            <div v-if="mode === 'add-project-name'" class="focus-content">
                <input
                    ref="nameInputEl"
                    v-model="nameInput"
                    type="text"
                    class="focus-input"
                    placeholder="what are you not doing..."
                    @keydown="onNameKeydown"
                />
            </div>

            <!-- Add Project Priority -->
            <div v-if="mode === 'add-project-priority'" class="focus-content">
                <div ref="prioritySelectorEl" class="priority-selector" tabindex="0" @keydown="onPriorityKeydown">
                    <div
                        class="priority-project-name"
                        :style="{ fontSize: priorityPreviewSize }"
                    >
                        {{ projectName }}
                    </div>
                    <div class="priority-dots">
                        <button
                            v-for="p in 5"
                            :key="p"
                            class="priority-dot"
                            :class="{ selected: selectedPriority >= p }"
                            @click="selectPriority(p)"
                        />
                    </div>
                    <div class="priority-label">how badly are you not doing this — press enter</div>
                </div>
            </div>

            <!-- Generating -->
            <div v-if="mode === 'generating'" class="focus-content">
                <div class="generating-text">
                    generating tasks<CursorBlink />
                </div>
            </div>

            <!-- Add Task -->
            <div v-if="mode === 'add-task'" class="focus-content focus-content-task">
                <div class="task-context">{{ projectName }}</div>
                <input
                    ref="taskInputEl"
                    v-model="taskInput"
                    type="text"
                    class="focus-input"
                    placeholder="add a task..."
                    @keydown="onTaskKeydown"
                />

                <!-- Stacked task pile -->
                <div v-if="addedTasks.length" class="task-pile">
                    <div
                        v-for="(task, i) in addedTasks"
                        :key="i"
                        class="task-pile-item"
                        :style="{
                            transform: `translateY(${i * -2}px) rotate(${(i % 2 === 0 ? -0.3 : 0.3) * (i + 1)}deg)`,
                            zIndex: addedTasks.length - i,
                            opacity: Math.max(0.2, 0.7 - i * 0.08),
                        }"
                    >
                        {{ task }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.focus-canvas {
    position: absolute;
    inset: 0;
    background: var(--nd-bg);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.6s ease;
    z-index: 100;
}

.focus-canvas.visible {
    opacity: 1;
    pointer-events: auto;
}

.focus-inner {
    width: 100%;
    max-width: 900px;
    padding: 0 3rem;
}

.focus-content {
    width: 100%;
}

.focus-content-task {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.focus-input {
    background: transparent;
    border: none;
    outline: none;
    color: var(--nd-white);
    font-family: var(--nd-font-mono);
    font-size: 22px;
    caret-color: var(--nd-red);
    width: 100%;
}

.focus-input::placeholder {
    color: var(--nd-ghost);
}

.priority-selector {
    text-align: center;
    outline: none;
}

.priority-project-name {
    font-family: var(--nd-font-display);
    font-weight: 900;
    color: var(--nd-white);
    text-transform: uppercase;
    letter-spacing: -1px;
    line-height: 1;
    margin-bottom: 24px;
    transition: font-size 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.priority-dots {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-bottom: 16px;
}

.priority-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid var(--nd-ghost);
    background: transparent;
    cursor: pointer;
    padding: 0;
    transition: border-color 0.25s ease, background 0.25s ease, transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.priority-dot:hover {
    border-color: var(--nd-dim);
    transform: scale(1.2);
}

.priority-dot.selected {
    background: var(--nd-red);
    border-color: var(--nd-red);
}

.priority-label {
    font-family: var(--nd-font-mono);
    font-size: 10px;
    color: var(--nd-ghost);
    letter-spacing: 1px;
}

.generating-text {
    font-family: var(--nd-font-mono);
    font-size: 14px;
    color: var(--nd-dim);
    text-align: center;
}

.task-context {
    font-family: var(--nd-font-mono);
    font-size: 13px;
    color: var(--nd-ghost);
}

.task-pile {
    display: flex;
    flex-direction: column-reverse;
    gap: 0;
    margin-top: 8px;
    position: relative;
}

.task-pile-item {
    font-family: var(--nd-font-mono);
    font-size: 12px;
    color: var(--nd-dim);
    padding: 6px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.02);
    animation: nd-pile-in 0.25s ease-out forwards;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

@keyframes nd-pile-in {
    from {
        opacity: 0;
        transform: translateY(-8px);
    }
    to {
        opacity: var(--pile-opacity, 0.4);
    }
}
</style>
