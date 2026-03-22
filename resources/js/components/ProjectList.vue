<script setup lang="ts">
import { computed } from 'vue';
import ProjectItem from '@/components/ProjectItem.vue';
import { useNotDoneStore } from '@/stores/useNotDoneStore';

defineProps<{
    newProjectId?: string | null;
}>();

const emit = defineEmits<{
    addTask: [projectId: string];
}>();

const store = useNotDoneStore();

const projects = computed(() => store.activeProjects);
</script>

<template>
    <div class="project-list">
        <ProjectItem
            v-for="project in projects"
            :key="project.id"
            :project="project"
            :is-new="project.id === newProjectId"
            @add-task="emit('addTask', $event)"
        />
    </div>
</template>

<style scoped>
.project-list {
    display: flex;
    flex-direction: column;
    justify-content: space-evenly;
    height: 100%;
    padding: 3.5rem 0;
}
</style>
