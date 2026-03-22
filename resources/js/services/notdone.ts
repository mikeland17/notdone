import { v4 as uuid } from 'uuid';

const PROJECTS_KEY = 'notdone_projects';
const TASKS_KEY = 'notdone_tasks';

export interface Project {
    id: string;
    name: string;
    priority: number;
    created_at: string;
    completed_at: string | null;
}

export interface Task {
    id: string;
    project_id: string;
    label: string;
    created_at: string;
    completed_at: string | null;
}

const load = <T>(key: string): T[] => JSON.parse(localStorage.getItem(key) || '[]');
const save = <T>(key: string, data: T[]): void => localStorage.setItem(key, JSON.stringify(data));

export const NotDoneService = {
    getProjects(): Project[] {
        return load<Project>(PROJECTS_KEY);
    },

    createProject({ name, priority = 3 }: { name: string; priority?: number }): Project {
        const projects = load<Project>(PROJECTS_KEY);
        const project: Project = {
            id: uuid(),
            name,
            priority,
            created_at: new Date().toISOString(),
            completed_at: null,
        };
        save(PROJECTS_KEY, [...projects, project]);
        return project;
    },

    completeProject(id: string): void {
        const projects = load<Project>(PROJECTS_KEY);
        save(
            PROJECTS_KEY,
            projects.map((p) => (p.id === id ? { ...p, completed_at: new Date().toISOString() } : p)),
        );
    },

    deleteProject(id: string): void {
        save(PROJECTS_KEY, load<Project>(PROJECTS_KEY).filter((p) => p.id !== id));
        save(TASKS_KEY, load<Task>(TASKS_KEY).filter((t) => t.project_id !== id));
    },

    getAllTasks(): Task[] {
        return load<Task>(TASKS_KEY);
    },

    getTasksForProject(projectId: string): Task[] {
        return load<Task>(TASKS_KEY).filter((t) => t.project_id === projectId);
    },

    createTask({ project_id, label }: { project_id: string; label: string }): Task {
        const tasks = load<Task>(TASKS_KEY);
        const task: Task = {
            id: uuid(),
            project_id,
            label,
            created_at: new Date().toISOString(),
            completed_at: null,
        };
        save(TASKS_KEY, [...tasks, task]);
        return task;
    },

    completeTask(id: string): void {
        const tasks = load<Task>(TASKS_KEY);
        save(
            TASKS_KEY,
            tasks.map((t) => (t.id === id ? { ...t, completed_at: new Date().toISOString() } : t)),
        );
    },

    deleteTask(id: string): void {
        save(TASKS_KEY, load<Task>(TASKS_KEY).filter((t) => t.id !== id));
    },
};
