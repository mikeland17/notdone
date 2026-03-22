export async function generateTasks(projectName: string, priority: number): Promise<string[]> {
    const response = await fetch('/api/generate-tasks', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ project: projectName, priority }),
    });

    const data = await response.json();
    return data.tasks;
}
