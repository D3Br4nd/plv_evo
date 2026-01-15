<script>
    import AdminLayout from "@/layouts/AdminLayout.svelte";
    import { router } from "@inertiajs/svelte";
    import { untrack } from "svelte";
    import { Button } from "@/lib/components/ui/button";
    import { Input } from "@/lib/components/ui/input";
    import { Label } from "@/lib/components/ui/label";
    import { Badge } from "@/lib/components/ui/badge";
    import * as Card from "@/lib/components/ui/card";
    import * as Dialog from "@/lib/components/ui/dialog";
    import { 
        Plus as PlusIcon, 
        Calendar as CalendarIcon, 
        Clock as ClockIcon, 
        Users as UsersIcon,
        Trash as TrashIcon,
        Layout as LayoutIcon,
        Flag as FlagIcon
    } from "lucide-svelte";

    import { onMount, onDestroy } from "svelte";
    import { Editor } from "@tiptap/core";
    import StarterKit from "@tiptap/starter-kit";
    import Link from "@tiptap/extension-link";
    import BoldIcon from "@tabler/icons-svelte/icons/bold";
    import ItalicIcon from "@tabler/icons-svelte/icons/italic";
    import ListIcon from "@tabler/icons-svelte/icons/list";

    let { projects, users, committees } = $props();

    // Local state for optimistic UI (synced via effect below)
    let localProjects = $state(untrack(() => projects));

    let columns = [
        {
            id: "todo",
            title: "Da Fare",
        },
        {
            id: "in_progress",
            title: "In Corso",
        },
        {
            id: "done",
            title: "Completato",
        },
    ];

    function getProjectsByStatus(status) {
        return localProjects.filter((p) => p.status === status);
    }

    let draggingId = $state(null);

    function handleDragStart(e, id) {
        draggingId = id;
        e.dataTransfer.effectAllowed = "move";
        // Hide ghost/add style if needed
    }

    function handleDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = "move";
    }

    function handleDrop(e, status) {
        e.preventDefault();
        if (!draggingId) return;

        // Optimistic Update
        const index = localProjects.findIndex((p) => p.id === draggingId);
        if (index !== -1 && localProjects[index].status !== status) {
            localProjects[index].status = status;

            // Persist
            router.patch(
                `/admin/projects/${draggingId}`,
                {
                    status: status,
                },
                {
                    preserveScroll: true,
                    onError: () => {
                        // Revert on error (could implement sophisticated revert here)
                        alert("Errore aggiornamento stato");
                        router.reload();
                    },
                },
            );
        }
        draggingId = null;
    }

    // New Project Dialog Logic
    let isNewProjectOpen = $state(false);
    let newProjectForm = $state({
        title: "",
        description: "",
        content: "",
        status: "todo",
        priority: "medium",
        committee_id: null,
        deadline: "",
        members: [], // array of IDs
    });
    let processing = $state(false);

    // Edit Project Dialog Logic
    let isEditProjectOpen = $state(false);
    let editProjectForm = $state(null);
    let editProjectId = $state(null);

    // Tiptap Editor for new project
    let editorElement = $state(null);
    let editor = $state(null);
    
    // Tiptap Editor for edit project
    let editEditorElement = $state(null);
    let editEditor = $state(null);

    $effect(() => {
        if (isNewProjectOpen && editorElement && !editor) {
            editor = new Editor({
                element: editorElement,
                extensions: [
                    StarterKit,
                    Link.configure({ openOnClick: false }),
                ],
                content: newProjectForm.content,
                editorProps: {
                    attributes: {
                        class: "prose prose-sm max-w-none min-h-[150px] p-3 focus:outline-none",
                    },
                },
                onUpdate: ({ editor }) => {
                    newProjectForm.content = editor.getHTML();
                },
            });
        }
    });
    
    $effect(() => {
        if (isEditProjectOpen && editEditorElement && !editEditor && editProjectForm) {
            editEditor = new Editor({
                element: editEditorElement,
                extensions: [
                    StarterKit,
                    Link.configure({ openOnClick: false }),
                ],
                content: editProjectForm.content || '',
                editorProps: {
                    attributes: {
                        class: "prose prose-sm max-w-none min-h-[150px] p-3 focus:outline-none",
                    },
                },
                onUpdate: ({ editor }) => {
                    editProjectForm.content = editor.getHTML();
                },
            });
        }
    });

    onDestroy(() => {
        if (editor) editor.destroy();
        if (editEditor) editEditor.destroy();
    });

    // Confirmation Dialog
    let confirmDeletionOpen = $state(false);
    let projectToDeleteId = $state(null);

    function createProject() {
        processing = true;
        router.post("/admin/projects", newProjectForm, {
            onSuccess: () => {
                isNewProjectOpen = false;
                newProjectForm = {
                    title: "",
                    description: "",
                    content: "",
                    status: "todo",
                    priority: "medium",
                    committee_id: null,
                    deadline: "",
                    members: [],
                };
                if (editor) editor.commands.setContent("");
                localProjects = projects;
            },
            onFinish: () => (processing = false),
        });
    }
    
    function openEditDialog(project) {
        editProjectId = project.id;
        editProjectForm = {
            title: project.title,
            description: project.description || '',
            content: project.content || '',
            status: project.status,
            priority: project.priority,
            committee_id: project.committee_id,
            deadline: project.deadline ? project.deadline.split('T')[0] : '',
            members: project.members?.map(m => m.id) || [],
        };
        isEditProjectOpen = true;
    }
    
    function closeEditDialog() {
        isEditProjectOpen = false;
        editProjectForm = null;
        editProjectId = null;
        if (editEditor) {
            editEditor.destroy();
            editEditor = null;
        }
    }
    
    function updateProject() {
        if (!editProjectId) return;
        processing = true;
        router.patch(`/admin/projects/${editProjectId}`, editProjectForm, {
            onSuccess: () => {
                closeEditDialog();
                localProjects = projects;
            },
            onFinish: () => (processing = false),
        });
    }

    function confirmDeleteProject() {
        if (!projectToDeleteId) return;
        processing = true;
        router.delete(`/admin/projects/${projectToDeleteId}`, {
            onSuccess: () => {
                confirmDeletionOpen = false;
                projectToDeleteId = null;
            },
            onFinish: () => (processing = false),
        });
    }

    // Sync local state when props change (e.g. after backend save)
    $effect(() => {
        localProjects = projects;
    });
</script>

<AdminLayout title="Progetti">
    <div class="h-full flex flex-col space-y-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Kanban Progetti</h1>
                <p class="text-sm text-muted-foreground">
                    Organizza e monitora i task associati ai comitati e ai soci.
                </p>
            </div>
            <Button
                onclick={() => (isNewProjectOpen = true)}
                class="shadow-sm transition-all hover:scale-105"
            >
                <PlusIcon class="mr-2 size-4" />
                Nuovo task
            </Button>
        </div>

        <!-- Kanban Board -->
        <div class="flex-1 overflow-x-auto pb-4">
            <div class="flex h-full space-x-6 min-w-[900px]">
                {#each columns as column}
                    <div
                        class="flex-1 flex flex-col min-w-[320px] max-w-sm rounded-xl border bg-muted/30 transition-colors"
                        ondragover={handleDragOver}
                        ondrop={(e) => handleDrop(e, column.id)}
                        role="region"
                        aria-label={column.title}
                    >
                        <div
                            class="flex items-center justify-between py-4 px-5"
                        >
                            <h3 class="text-sm font-semibold tracking-tight text-foreground">
                                {column.title}
                            </h3>
                            <Badge variant="secondary" class="rounded-full px-2 text-[10px] font-bold">
                                {getProjectsByStatus(column.id).length}
                            </Badge>
                        </div>

                        <div class="flex-1 space-y-3 overflow-y-auto p-3 pt-0">
                            {#each getProjectsByStatus(column.id) as project (project.id)}
                                <div
                                    class={[
                                        "group relative flex flex-col rounded-lg border bg-card p-4 shadow-sm transition-all hover:shadow-md hover:ring-1 hover:ring-primary/20 active:scale-[0.98] cursor-grab active:cursor-grabbing border-l-4",
                                        project.priority === 'high' ? "border-l-destructive" : "",
                                        project.priority === 'medium' ? "border-l-orange-500" : "",
                                        project.priority === 'low' ? "border-l-muted-foreground/30" : ""
                                    ].join(" ")}
                                    draggable="true"
                                    ondragstart={(e) =>
                                        handleDragStart(e, project.id)}
                                    role="listitem"
                                >
                                    <div class="flex items-start justify-between mb-2">
                                        <div class="flex flex-wrap gap-1">
                                            {#if project.priority === "high"}
                                                <Badge variant="destructive" class="text-[9px] h-4 px-1.5 uppercase tracking-tighter">Alta</Badge>
                                            {:else if project.priority === "medium"}
                                                <Badge variant="secondary" class="text-[9px] h-4 px-1.5 uppercase tracking-tighter bg-orange-500/10 text-orange-600 border-orange-200 dark:border-orange-900">Media</Badge>
                                            {:else}
                                                <Badge variant="outline" class="text-[9px] h-4 px-1.5 uppercase tracking-tighter text-muted-foreground">Bassa</Badge>
                                            {/if}
                                        </div>

                                        <div class="flex -space-x-1.5">
                                            {#each (project.members || []).slice(0, 3) as member}
                                                {#if member.avatar_url}
                                                    <img 
                                                        src={member.avatar_url}
                                                        alt={member.name}
                                                        title={member.name}
                                                        class="inline-block h-6 w-6 rounded-full ring-2 ring-card object-cover shadow-sm hover:scale-110 transition-transform cursor-help"
                                                    />
                                                {:else}
                                                    <div 
                                                        class="inline-flex h-6 w-6 items-center justify-center rounded-full ring-2 ring-card bg-gradient-to-br from-primary/80 to-primary text-primary-foreground text-[9px] font-bold shadow-sm hover:scale-110 transition-transform cursor-help"
                                                        title={member.name}
                                                    >
                                                        {member.name.charAt(0).toUpperCase()}
                                                    </div>
                                                {/if}
                                            {/each}
                                            {#if project.members?.length > 3}
                                                <div class="inline-flex h-6 w-6 items-center justify-center rounded-full ring-2 ring-card bg-muted text-muted-foreground text-[9px] font-bold shadow-sm">
                                                    +{project.members.length - 3}
                                                </div>
                                            {/if}
                                        </div>
                                    </div>

                                    <h4 class="text-sm font-semibold leading-snug mb-2 transition-colors group-hover:text-primary">
                                        {project.title}
                                    </h4>

                                    {#if project.committee}
                                        <div class="flex items-center gap-1.5 text-[10px] text-muted-foreground font-medium truncate mb-3 bg-muted/40 py-0.5 px-2 rounded-full w-fit max-w-full">
                                            <UsersIcon class="size-2.5" />
                                            <span class="truncate">{project.committee.name}</span>
                                        </div>
                                    {/if}

                                    <div class="mt-auto pt-3 flex items-center justify-between border-t border-dashed border-muted">
                                        {#if project.deadline}
                                            <div class="flex items-center gap-1 text-[10px] font-medium text-muted-foreground">
                                                <CalendarIcon class="size-2.5" />
                                                <span>{new Date(project.deadline).toLocaleDateString('it-IT', { day: '2-digit', month: 'short' })}</span>
                                            </div>
                                        {:else}
                                            <div></div>
                                        {/if}

                                        <div class="flex gap-1">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                class="size-6 opacity-0 group-hover:opacity-100 transition-opacity text-primary hover:text-primary hover:bg-primary/10"
                                                onclick={(e) => {
                                                    e.stopPropagation();
                                                    openEditDialog(project);
                                                }}
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                class="size-6 opacity-0 group-hover:opacity-100 transition-opacity text-destructive hover:text-destructive hover:bg-destructive/10"
                                                onclick={(e) => {
                                                    e.stopPropagation();
                                                    projectToDeleteId = project.id;
                                                    confirmDeletionOpen = true;
                                                }}
                                            >
                                                <TrashIcon class="size-3" />
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            {/each}
                        </div>
                    </div>
                {/each}
            </div>
        </div>
    </div>

    <Dialog.Root bind:open={isNewProjectOpen}>
        <Dialog.Content class="max-w-2xl max-h-[95vh] overflow-y-auto bg-background p-0 border shadow-2xl rounded-2xl overflow-hidden flex flex-col">
            <div class="p-6 border-b bg-muted/20">
                <Dialog.Title class="text-xl font-bold tracking-tight">Nuovo task</Dialog.Title>
                <Dialog.Description class="text-xs text-muted-foreground mt-1">Crea un'attività dettagliata e assegnala ai membri.</Dialog.Description>
            </div>

            <div class="p-6 space-y-6 flex-1">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Left Column -->
                    <div class="space-y-4">
                        <div class="space-y-2">
                            <Label for="title" class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Titolo <span class="text-destructive">*</span></Label>
                            <Input id="title" bind:value={newProjectForm.title} placeholder="Es: Organizzazione stand" class="h-9 text-sm" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="priority" class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Priorità</Label>
                                <select
                                    id="priority"
                                    bind:value={newProjectForm.priority}
                                    class="h-9 w-full rounded-md border border-input bg-background px-3 text-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring transition-shadow"
                                >
                                    <option value="low">Bassa</option>
                                    <option value="medium">Media</option>
                                    <option value="high">Alta</option>
                                </select>
                            </div>

                            <div class="space-y-2">
                                <Label for="deadline" class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Deadline</Label>
                                <Input id="deadline" type="date" bind:value={newProjectForm.deadline} class="h-9 text-xs" />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="committee" class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Comitato</Label>
                            <select
                                id="committee"
                                bind:value={newProjectForm.committee_id}
                                class="h-9 w-full rounded-md border border-input bg-background px-3 text-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring transition-shadow"
                            >
                                <option value={null}>Nessuno / Generale</option>
                                {#each committees as c}
                                    <option value={c.id}>{c.name}</option>
                                {/each}
                            </select>
                        </div>
                    </div>

                    <!-- Right Column (Members) -->
                    <div class="space-y-2 flex flex-col h-full">
                        <Label class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Assegna a</Label>
                        <div class="flex-1 border rounded-md p-3 max-h-[170px] md:max-h-full overflow-y-auto space-y-1.5 bg-muted/30">
                            {#each users as user}
                                <label class="flex items-center gap-2 text-xs cursor-pointer hover:bg-muted/50 p-1.5 rounded-md transition-all group">
                                    <input 
                                        type="checkbox" 
                                        value={user.id} 
                                        checked={newProjectForm.members.includes(user.id)}
                                        onchange={(e) => {
                                            if (e.target.checked) {
                                                newProjectForm.members = [...newProjectForm.members, user.id];
                                            } else {
                                                newProjectForm.members = newProjectForm.members.filter(id => id !== user.id);
                                            }
                                        }}
                                        class="size-3.5 rounded border-input text-primary focus:ring-primary/20 bg-background"
                                    />
                                    <span class="truncate group-hover:text-primary transition-colors">{user.name}</span>
                                </label>
                            {/each}
                        </div>
                    </div>
                </div>

                <!-- Full Width (Rich Text) -->
                <div class="space-y-2">
                    <Label class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Descrizione Dettagliata</Label>
                    <div class="rounded-lg border overflow-hidden bg-background focus-within:ring-1 focus-within:ring-ring transition-shadow">
                        <div class="flex flex-wrap gap-0.5 border-b bg-muted/30 p-1.5">
                            <Button 
                                variant="ghost" size="icon" 
                                class={["size-7", editor?.isActive('bold') ? "bg-accent text-accent-foreground" : "text-muted-foreground"].join(" ")}
                                onclick={() => editor?.chain().focus().toggleBold().run()}
                            >
                                <BoldIcon class="size-4" />
                            </Button>
                            <Button 
                                variant="ghost" size="icon" 
                                class={["size-7", editor?.isActive('italic') ? "bg-accent text-accent-foreground" : "text-muted-foreground"].join(" ")}
                                onclick={() => editor?.chain().focus().toggleItalic().run()}
                            >
                                <ItalicIcon class="size-4" />
                            </Button>
                            <Button 
                                variant="ghost" size="icon" 
                                class={["size-7", editor?.isActive('bulletList') ? "bg-accent text-accent-foreground" : "text-muted-foreground"].join(" ")}
                                onclick={() => editor?.chain().focus().toggleBulletList().run()}
                            >
                                <ListIcon class="size-4" />
                            </Button>
                        </div>
                        <div bind:this={editorElement} class="min-h-[180px] text-sm overflow-y-auto max-h-[300px]"></div>
                    </div>
                </div>
            </div>

            <div class="p-6 border-t bg-muted/10 flex justify-end gap-3">
                <Dialog.Close>
                    {#snippet child({ props })}
                        <Button {...props} variant="ghost" size="sm" class="px-5">Annulla</Button>
                    {/snippet}
                </Dialog.Close>
                <Button onclick={createProject} disabled={processing} size="sm" class="px-8 shadow-sm">
                    {processing ? "Salvataggio..." : "Crea Task"}
                </Button>
            </div>
        </Dialog.Content>
    </Dialog.Root>

    <Dialog.Root bind:open={confirmDeletionOpen}>
        <Dialog.Content class="max-w-sm rounded-xl border bg-background shadow-2xl">
            <Dialog.Header class="p-0">
                <Dialog.Title class="text-lg font-bold">Elimina Task</Dialog.Title>
                <Dialog.Description class="pt-2 text-sm leading-normal">
                    Sei sicuro di voler eliminare questo task? Questa azione non
                    può essere annullata.
                </Dialog.Description>
            </Dialog.Header>
            <Dialog.Footer class="mt-6 flex gap-2 sm:justify-end">
                <Button
                    variant="ghost"
                    size="sm"
                    class="flex-1 sm:flex-none"
                    onclick={() => (confirmDeletionOpen = false)}
                >
                    Annulla
                </Button>
                <Button
                    variant="destructive"
                    size="sm"
                    class="flex-1 sm:flex-none"
                    onclick={confirmDeleteProject}
                    disabled={processing}
                >
                    {processing ? "Eliminazione..." : "Elimina"}
                </Button>
            </Dialog.Footer>
        </Dialog.Content>
    </Dialog.Root>
    
    <!-- Edit Project Dialog -->
    {#if editProjectForm}
    <Dialog.Root bind:open={isEditProjectOpen} onOpenChange={(open) => { if (!open) closeEditDialog(); }}>
        <Dialog.Content class="max-w-2xl max-h-[95vh] overflow-y-auto bg-background p-0 border shadow-2xl rounded-2xl overflow-hidden flex flex-col">
            <div class="p-6 border-b bg-muted/20">
                <Dialog.Title class="text-xl font-bold tracking-tight">Modifica task</Dialog.Title>
                <Dialog.Description class="text-xs text-muted-foreground mt-1">Aggiorna i dettagli del task.</Dialog.Description>
            </div>

            <div class="p-6 space-y-6 flex-1">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Left Column -->
                    <div class="space-y-4">
                        <div class="space-y-2">
                            <Label for="edit-title" class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Titolo <span class="text-destructive">*</span></Label>
                            <Input id="edit-title" bind:value={editProjectForm.title} placeholder="Es: Organizzazione stand" class="h-9 text-sm" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="edit-priority" class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Priorità</Label>
                                <select
                                    id="edit-priority"
                                    bind:value={editProjectForm.priority}
                                    class="h-9 w-full rounded-md border border-input bg-background px-3 text-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring transition-shadow"
                                >
                                    <option value="low">Bassa</option>
                                    <option value="medium">Media</option>
                                    <option value="high">Alta</option>
                                </select>
                            </div>

                            <div class="space-y-2">
                                <Label for="edit-deadline" class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Deadline</Label>
                                <Input id="edit-deadline" type="date" bind:value={editProjectForm.deadline} class="h-9 text-xs" />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="edit-committee" class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Comitato</Label>
                            <select
                                id="edit-committee"
                                bind:value={editProjectForm.committee_id}
                                class="h-9 w-full rounded-md border border-input bg-background px-3 text-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring transition-shadow"
                            >
                                <option value={null}>Nessuno / Generale</option>
                                {#each committees as c}
                                    <option value={c.id}>{c.name}</option>
                                {/each}
                            </select>
                        </div>
                    </div>

                    <!-- Right Column (Members) -->
                    <div class="space-y-2 flex flex-col h-full">
                        <Label class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Assegna a</Label>
                        <div class="flex-1 border rounded-md p-3 max-h-[170px] md:max-h-full overflow-y-auto space-y-1.5 bg-muted/30">
                            {#each users as user}
                                <label class="flex items-center gap-2 text-xs cursor-pointer hover:bg-muted/50 p-1.5 rounded-md transition-all group">
                                    <input 
                                        type="checkbox" 
                                        value={user.id} 
                                        checked={editProjectForm.members.includes(user.id)}
                                        onchange={(e) => {
                                            if (e.target.checked) {
                                                editProjectForm.members = [...editProjectForm.members, user.id];
                                            } else {
                                                editProjectForm.members = editProjectForm.members.filter(id => id !== user.id);
                                            }
                                        }}
                                        class="size-3.5 rounded border-input text-primary focus:ring-primary/20 bg-background"
                                    />
                                    <span class="truncate group-hover:text-primary transition-colors">{user.name}</span>
                                </label>
                            {/each}
                        </div>
                    </div>
                </div>

                <!-- Full Width (Rich Text) -->
                <div class="space-y-2">
                    <Label class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Descrizione Dettagliata</Label>
                    <div class="rounded-lg border overflow-hidden bg-background focus-within:ring-1 focus-within:ring-ring transition-shadow">
                        <div class="flex flex-wrap gap-0.5 border-b bg-muted/30 p-1.5">
                            <Button 
                                variant="ghost" size="icon" 
                                class={["size-7", editEditor?.isActive('bold') ? "bg-accent text-accent-foreground" : "text-muted-foreground"].join(" ")}
                                onclick={() => editEditor?.chain().focus().toggleBold().run()}
                            >
                                <BoldIcon class="size-4" />
                            </Button>
                            <Button 
                                variant="ghost" size="icon" 
                                class={["size-7", editEditor?.isActive('italic') ? "bg-accent text-accent-foreground" : "text-muted-foreground"].join(" ")}
                                onclick={() => editEditor?.chain().focus().toggleItalic().run()}
                            >
                                <ItalicIcon class="size-4" />
                            </Button>
                            <Button 
                                variant="ghost" size="icon" 
                                class={["size-7", editEditor?.isActive('bulletList') ? "bg-accent text-accent-foreground" : "text-muted-foreground"].join(" ")}
                                onclick={() => editEditor?.chain().focus().toggleBulletList().run()}
                            >
                                <ListIcon class="size-4" />
                            </Button>
                        </div>
                        <div bind:this={editEditorElement} class="min-h-[180px] text-sm overflow-y-auto max-h-[300px]"></div>
                    </div>
                </div>
            </div>

            <div class="p-6 border-t bg-muted/10 flex justify-end gap-3">
                <Button variant="ghost" size="sm" class="px-5" onclick={closeEditDialog}>Annulla</Button>
                <Button onclick={updateProject} disabled={processing} size="sm" class="px-8 shadow-sm">
                    {processing ? "Salvataggio..." : "Salva Modifiche"}
                </Button>
            </div>
        </Dialog.Content>
    </Dialog.Root>
    {/if}
</AdminLayout>
