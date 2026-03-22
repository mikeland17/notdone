<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import ProjectList from '@/components/ProjectList.vue';
import { useNotDoneStore } from '@/stores/useNotDoneStore';

defineProps<{
    newProjectId?: string | null;
}>();

const emit = defineEmits<{
    addProject: [];
    addTask: [projectId: string];
}>();

const store = useNotDoneStore();

const remainingCount = computed(() => store.remainingCount);
const allDone = computed(() => store.allDone);
const hasProjects = computed(() => store.projects.length > 0);

// Session tracking
const baselineRemaining = ref(0);
const sessionCheckedOff = ref(0);
const initialLoadDone = ref(false);
const showSubtext = ref(false);
let prevRemainingCount = 0;

const titleEl = ref<HTMLElement | null>(null);

onMounted(() => {
    setTimeout(() => {
        const remaining = store.tasks.filter((t) => !t.completed_at).length;
        baselineRemaining.value = remaining;
        prevRemainingCount = remaining;
        initialLoadDone.value = true;
    }, 100);
});

// Watch for tasks being checked off
watch(
    () => store.tasks.filter((t) => !t.completed_at).length,
    (currentRemaining) => {
        if (!initialLoadDone.value) {
            return;
        }
        if (currentRemaining < prevRemainingCount) {
            sessionCheckedOff.value += prevRemainingCount - currentRemaining;
        } else if (currentRemaining > prevRemainingCount) {
            baselineRemaining.value += currentRemaining - prevRemainingCount;
        }
        prevRemainingCount = currentRemaining;
    },
);

// Session-aware "all done" — only true if you checked stuff off THIS session
const sessionAllDone = computed(() => allDone.value && sessionCheckedOff.value > 0);

// NOT opacity: starts at 1, fades proportionally per check-off
const notOpacity = computed(() => {
    if (sessionCheckedOff.value === 0 || baselineRemaining.value === 0) {
        return 1;
    }
    if (sessionAllDone.value) {
        return 0;
    }
    const progress = sessionCheckedOff.value / baselineRemaining.value;
    return Math.max(0.06, 1 - progress * 0.94);
});

// NOT font size: shrinks as tasks are checked off — gives contrast to DONE growing
const notFontSize = computed<string | undefined>(() => {
    if (sessionCheckedOff.value === 0) {
        return undefined;
    }
    const shrinkPercent = Math.max(50, 100 - sessionCheckedOff.value * 5);
    return `${shrinkPercent}%`;
});

// DONE font size in vw: null = use CSS default, grows 1.5vw per check-off, 22vw when all done this session
const doneFontSizeVw = computed<number | null>(() => {
    if (sessionCheckedOff.value === 0) {
        return null;
    }
    if (sessionAllDone.value) {
        return 22;
    }
    return 9 + sessionCheckedOff.value * 1.5;
});

const statusText = computed(() => {
    if (!hasProjects.value) {
        return '';
    }
    if (allDone.value) {
        return '— DONE';
    }
    return `— ${remainingCount.value} REMAINING`;
});

// Show subtext a few seconds after session-done
watch(sessionAllDone, (done) => {
    if (done) {
        setTimeout(() => {
            showSubtext.value = true;
        }, 3500);
    }
});

function resetAfterCelebration() {
    showSubtext.value = false;
    sessionCheckedOff.value = 0;
    const remaining = store.tasks.filter((t) => !t.completed_at).length;
    baselineRemaining.value = remaining;
    prevRemainingCount = remaining;
    emit('addProject');
}
</script>

<template>
    <div class="nd-dashboard">
        <!-- Left zone: branding -->
        <div class="nd-left">
            <div class="nd-brand">
                <div ref="titleEl" class="nd-title" :style="doneFontSizeVw !== null ? { fontSize: doneFontSizeVw + 'vw' } : undefined">
                    <span class="nd-not" :style="{ opacity: notOpacity, fontSize: notFontSize }">NOT</span>
                    <br />
                    <span class="nd-done-text">DONE</span><span class="nd-dot" :class="{ done: sessionAllDone }" />
                </div>
                <div v-if="hasProjects && !sessionAllDone" class="nd-count">
                    {{ statusText }}
                </div>
            </div>

            <div v-if="!sessionAllDone" class="nd-bottom-left">
                <button class="nd-add-project" @click="emit('addProject')">+ add shit to your list</button>
            </div>
        </div>

        <!-- Right zone: projects -->
        <div class="nd-right" :style="{ opacity: sessionAllDone ? 0 : 1 }">
            <ProjectList :new-project-id="newProjectId" @add-task="emit('addTask', $event)" />
        </div>

        <!-- Celebration subtext — sits under DONE in the left column -->
        <div v-if="showSubtext" class="nd-celeb-subtext">
            <span class="nd-celeb-line">i bet you have more to do</span>
            <button class="nd-celeb-cta" @click="resetAfterCelebration">+ add more stuff</button>
        </div>
    </div>
</template>

<style scoped>
.nd-dashboard {
    display: flex;
    width: 100%;
    height: 100%;
    position: relative;
}

.nd-left {
    width: 60%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 3.5rem;
}

.nd-brand {
    user-select: none;
}

.nd-title {
    font-family: var(--nd-font-display);
    font-size: clamp(48px, 9vw, 114px);
    font-weight: 900;
    color: var(--nd-white);
    line-height: 0.86;
    text-transform: uppercase;
    letter-spacing: -2px;
    transition: all 1.25s ease;
}

.nd-not {
    transition: all 1.25s ease;
}

.nd-dot {
    display: inline-block;
    width: 0.16em;
    height: 0.16em;
    border-radius: 50%;
    background: var(--nd-red);
    margin-left: 0.06em;
    vertical-align: 0.08em;
    animation: nd-pulse 2.4s ease-in-out infinite;
    transition: all 1.25s ease;
}

.nd-dot.done {
    background: var(--nd-green);
    animation: none;
}

.nd-count {
    color: var(--nd-ghost);
    font-family: var(--nd-font-mono);
    font-size: 10px;
    letter-spacing: 4px;
    text-transform: uppercase;
    margin-top: 28px;
    transition: all 1.25s ease;
}

.nd-bottom-left {
    /* sits at the bottom of the left column */
}

.nd-add-project {
    background: none;
    border: none;
    color: var(--nd-ghost);
    font-family: var(--nd-font-mono);
    font-size: 11px;
    cursor: pointer;
    padding: 0;
    transition: color 0.2s ease;
}

.nd-add-project:hover {
    color: var(--nd-red);
}

.nd-right {
    width: 40%;
    padding-right: 3.5rem;
    transition: opacity 1.25s ease;
}

.nd-celeb-subtext {
    position: absolute;
    bottom: 3.5rem;
    left: 3.5rem;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
    animation: nd-fade-up 0.8s ease forwards;
    z-index: 10;
}

.nd-celeb-line {
    font-family: var(--nd-font-mono);
    font-size: 13px;
    color: var(--nd-dim);
    letter-spacing: 1px;
}

.nd-celeb-cta {
    background: none;
    border: 1px solid var(--nd-ghost);
    color: var(--nd-muted);
    font-family: var(--nd-font-mono);
    font-size: 12px;
    letter-spacing: 1px;
    padding: 10px 28px;
    cursor: pointer;
    transition: color 0.3s ease, border-color 0.3s ease;
}

.nd-celeb-cta:hover {
    color: var(--nd-white);
    border-color: var(--nd-green);
}

/* Responsive: stack on small screens */
@media (max-width: 768px) {
    .nd-dashboard {
        flex-direction: column;
    }

    .nd-left {
        width: 100%;
        padding: 2rem;
        min-height: auto;
    }

    .nd-right {
        width: 100%;
        padding: 0 2rem 2rem;
    }

    .nd-bottom-left {
        margin-top: 2rem;
    }
}
</style>
