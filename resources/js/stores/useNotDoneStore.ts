import { defineStore } from 'pinia';
import type { Project, Task } from '@/services/notdone';
import { NotDoneService } from '@/services/notdone';

export const useNotDoneStore = defineStore('notdone', {
    state: () => ({
        projects: [] as Project[],
        tasks: [] as Task[],
    }),

    getters: {
        activeProjects: (state): Project[] =>
            state.projects.filter((p) => !p.completed_at).sort((a, b) => b.priority - a.priority),

        completedProjects: (state): Project[] => state.projects.filter((p) => !!p.completed_at),

        tasksForProject:
            (state) =>
            (projectId: string): Task[] =>
                state.tasks.filter((t) => t.project_id === projectId),

        activeTasksForProject:
            (state) =>
            (projectId: string): Task[] =>
                state.tasks.filter((t) => t.project_id === projectId && !t.completed_at),

        remainingCount(): number {
            return this.activeProjects.length;
        },

        allDone(): boolean {
            return this.projects.length > 0 && this.activeProjects.length === 0;
        },

        /** 0 to 1 — proportion of all tasks completed across all projects */
        completionProgress(state): number {
            const total = state.tasks.length;
            if (total === 0) {
                return 0;
            }
            const completed = state.tasks.filter((t) => t.completed_at).length;
            return completed / total;
        },
    },

    actions: {
        load() {
            this.projects = NotDoneService.getProjects();
            this.tasks = NotDoneService.getAllTasks();
        },

        addProject(data: { name: string; priority?: number }): Project {
            const project = NotDoneService.createProject(data);
            this.projects.push(project);
            return project;
        },

        completeProject(id: string) {
            NotDoneService.completeProject(id);
            const project = this.projects.find((p) => p.id === id);
            if (project) {
                project.completed_at = new Date().toISOString();
            }
        },

        deleteProject(id: string) {
            NotDoneService.deleteProject(id);
            this.projects = this.projects.filter((p) => p.id !== id);
            this.tasks = this.tasks.filter((t) => t.project_id !== id);
        },

        addTask(data: { project_id: string; label: string }): Task {
            const task = NotDoneService.createTask(data);
            this.tasks.push(task);
            return task;
        },

        completeTask(id: string) {
            NotDoneService.completeTask(id);
            const task = this.tasks.find((t) => t.id === id);
            if (task) {
                task.completed_at = new Date().toISOString();
            }
        },

        deleteTask(id: string) {
            NotDoneService.deleteTask(id);
            this.tasks = this.tasks.filter((t) => t.id !== id);
        },
    },
});
