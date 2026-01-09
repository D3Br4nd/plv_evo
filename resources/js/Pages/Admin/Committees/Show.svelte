<script>
    import AdminLayout from "@/layouts/AdminLayout.svelte";
    import { Button } from "@/lib/components/ui/button";
    import * as Card from "@/lib/components/ui/card";
    import * as Dialog from "@/lib/components/ui/dialog";
    import * as Select from "@/lib/components/ui/select";
    import * as Popover from "@/lib/components/ui/popover";
    import * as Command from "@/lib/components/ui/command";
    import { cn } from "@/lib/utils";
    import * as Table from "@/lib/components/ui/table";
    import { Input } from "@/lib/components/ui/input";
    import { Label } from "@/lib/components/ui/label";
    import { Textarea } from "@/lib/components/ui/textarea";
    import { Badge } from "@/lib/components/ui/badge";
    import { Switch } from "@/lib/components/ui/switch";
    import { router } from "@inertiajs/svelte";
    import PlusIcon from "@tabler/icons-svelte/icons/plus";
    import TrashIcon from "@tabler/icons-svelte/icons/trash";
    import UserIcon from "@tabler/icons-svelte/icons/user";
    import UploadIcon from "@tabler/icons-svelte/icons/upload";
    import Trash2Icon from "@tabler/icons-svelte/icons/trash";
    import CheckIcon from "@tabler/icons-svelte/icons/check";
    import SelectorIcon from "@tabler/icons-svelte/icons/selector";
    import { formatDistanceToNow } from "date-fns";
    import { it } from "date-fns/locale";

    let { committee, availableMembers = [] } = $props();

    let addMemberDialogOpen = $state(false);
    let createPostDialogOpen = $state(false);
    let openCombobox = $state(false);

    let selectedMemberId = $state(null);
    let memberRole = $state("");

    let postFormData = $state({
        title: "",
        content: "",
    });

    // Image Upload State
    let selectedImageFile = $state(null);
    let imageInputRef = $state(null);

    // Confirmation Dialogs
    let confirmRemovalOpen = $state(false);
    let memberToRemoveId = $state(null);
    let memberToRemoveName = $state("");

    let confirmPostDeletionOpen = $state(false);
    let postToDeleteId = $state(null);

    let confirmImageDeletionOpen = $state(false);

    function handleAddMember() {
        if (!selectedMemberId) return;

        router.post(
            `/admin/committees/${committee.id}/members`,
            {
                user_id: selectedMemberId,
                role: memberRole || null,
            },
            {
                onSuccess: () => {
                    addMemberDialogOpen = false;
                    selectedMemberId = null;
                    memberRole = "";
                },
            },
        );
    }

    function handleRemoveMember(userId, userName) {
        memberToRemoveId = userId;
        memberToRemoveName = userName;
        confirmRemovalOpen = true;
    }

    function confirmRemoveMember() {
        if (!memberToRemoveId) return;
        router.delete(
            `/admin/committees/${committee.id}/members/${memberToRemoveId}`,
            {
                onSuccess: () => {
                    confirmRemovalOpen = false;
                    memberToRemoveId = null;
                    memberToRemoveName = "";
                },
            },
        );
    }

    function handleCreatePost() {
        router.post(`/admin/committees/${committee.id}/posts`, postFormData, {
            onSuccess: () => {
                createPostDialogOpen = false;
                postFormData = { title: "", content: "" };
            },
        });
    }

    function handleStatusToggle() {
        const newStatus = committee.status === "active" ? "inactive" : "active";
        router.patch(
            `/admin/committees/${committee.id}`,
            {
                name: committee.name,
                status: newStatus,
            },
            {
                preserveScroll: true,
            },
        );
    }

    function handleImageUpload(e) {
        const file = e.target.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append("image", file);

        router.post(`/admin/committees/${committee.id}/image`, formData, {
            preserveScroll: true,
            onSuccess: () => {
                selectedImageFile = null;
                if (imageInputRef) {
                    imageInputRef.value = "";
                }
            },
        });
    }

    function confirmDeleteImage() {
        router.delete(`/admin/committees/${committee.id}/image`, {
            preserveScroll: true,
            onSuccess: () => {
                confirmImageDeletionOpen = false;
            },
        });
    }

    function handleDeletePost(postId) {
        postToDeleteId = postId;
        confirmPostDeletionOpen = true;
    }

    function confirmDeletePost() {
        if (!postToDeleteId) return;
        router.delete(
            `/admin/committees/${committee.id}/posts/${postToDeleteId}`,
            {
                onSuccess: () => {
                    confirmPostDeletionOpen = false;
                    postToDeleteId = null;
                },
            },
        );
    }
</script>

<AdminLayout title={committee.name}>
    <div class="space-y-6">
        <!-- Committee Header -->
        <div>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-6">
                    <!-- Committee Image -->
                    <div class="relative group">
                        <div
                            class="size-24 rounded-2xl border-2 border-dashed border-muted-foreground/25 bg-muted flex items-center justify-center overflow-hidden transition-all group-hover:border-primary/50"
                        >
                            {#if committee.image_url}
                                <img
                                    src={committee.image_url}
                                    alt={committee.name}
                                    class="h-full w-full object-cover"
                                />
                            {:else}
                                <div class="text-center p-2">
                                    <UserIcon
                                        class="size-8 mx-auto text-muted-foreground/50 mb-1"
                                    />
                                    <span
                                        class="text-[10px] text-muted-foreground uppercase font-semibold tracking-wider"
                                        >No Logo</span
                                    >
                                </div>
                            {/if}
                        </div>

                        <!-- Upload/Delete Overlay -->
                        <div
                            class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2"
                        >
                            <Button
                                variant="secondary"
                                size="icon"
                                class="size-8 rounded-full"
                                onclick={() => imageInputRef?.click()}
                                title="Carica logo"
                            >
                                <UploadIcon class="size-4" />
                            </Button>
                            {#if committee.image_url}
                                <Button
                                    variant="destructive"
                                    size="icon"
                                    class="size-8 rounded-full"
                                    onclick={() =>
                                        (confirmImageDeletionOpen = true)}
                                    title="Rimuovi logo"
                                >
                                    <Trash2Icon class="size-4" />
                                </Button>
                            {/if}
                        </div>

                        <input
                            type="file"
                            accept="image/*"
                            class="hidden"
                            bind:this={imageInputRef}
                            onchange={handleImageUpload}
                        />
                    </div>

                    <div>
                        <h1 class="text-3xl font-bold tracking-tight">
                            {committee.name}
                        </h1>
                        {#if committee.description}
                            <p class="mt-2 text-muted-foreground">
                                {committee.description}
                            </p>
                        {/if}
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <Label
                            for="committee-status"
                            class="text-xs font-medium uppercase tracking-wider text-muted-foreground"
                        >
                            {committee.status === "active"
                                ? "Attivo"
                                : "Inattivo"}
                        </Label>
                        <Switch
                            id="committee-status"
                            checked={committee.status === "active"}
                            onCheckedChange={handleStatusToggle}
                        />
                    </div>
                    <Badge
                        variant={committee.status === "active"
                            ? "default"
                            : "secondary"}
                    >
                        {committee.status === "active" ? "Attivo" : "Inattivo"}
                    </Badge>
                </div>
            </div>
        </div>

        <!-- Members Section -->
        <Card.Root>
            <Card.Header>
                <div class="flex items-center justify-between">
                    <div>
                        <Card.Title>Membri del Comitato</Card.Title>
                        <Card.Description>
                            {committee.members.length}
                            {committee.members.length === 1
                                ? "membro"
                                : "membri"} associati
                        </Card.Description>
                    </div>
                    <Button
                        onclick={() => (addMemberDialogOpen = true)}
                        disabled={availableMembers.length === 0}
                    >
                        <PlusIcon class="mr-2 size-4" />
                        Aggiungi Socio
                    </Button>
                </div>
            </Card.Header>
            <Card.Content>
                {#if committee.members.length === 0}
                    <div
                        class="flex flex-col items-center justify-center py-8 text-center"
                    >
                        <UserIcon class="mb-3 size-12 text-muted-foreground" />
                        <p class="mb-2 text-sm font-medium">Nessun membro</p>
                        <p class="text-sm text-muted-foreground">
                            Aggiungi dei membri a questo comitato.
                        </p>
                    </div>
                {:else}
                    <Table.Root>
                        <Table.Header>
                            <Table.Row>
                                <Table.Head>Nome</Table.Head>
                                <Table.Head>Email</Table.Head>
                                <Table.Head>Ruolo</Table.Head>
                                <Table.Head>Aggiunto il</Table.Head>
                                <Table.Head class="w-12"></Table.Head>
                            </Table.Row>
                        </Table.Header>
                        <Table.Body>
                            {#each committee.members as member}
                                <Table.Row>
                                    <Table.Cell class="font-medium"
                                        >{member.name}</Table.Cell
                                    >
                                    <Table.Cell>{member.email}</Table.Cell>
                                    <Table.Cell>
                                        {member.pivot.role || "-"}
                                    </Table.Cell>
                                    <Table.Cell class="text-muted-foreground">
                                        {new Date(
                                            member.pivot.joined_at,
                                        ).toLocaleDateString("it-IT")}
                                    </Table.Cell>
                                    <Table.Cell>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            onclick={() =>
                                                handleRemoveMember(
                                                    member.id,
                                                    member.name,
                                                )}
                                        >
                                            <TrashIcon
                                                class="size-4 text-destructive"
                                            />
                                        </Button>
                                    </Table.Cell>
                                </Table.Row>
                            {/each}
                        </Table.Body>
                    </Table.Root>
                {/if}
            </Card.Content>
        </Card.Root>

        <!-- Posts Section (Bulletin Board) -->
        <Card.Root>
            <Card.Header>
                <div class="flex items-center justify-between">
                    <div>
                        <Card.Title>Bacheca</Card.Title>
                        <Card.Description>
                            Post e comunicazioni del comitato
                        </Card.Description>
                    </div>
                    <Button onclick={() => (createPostDialogOpen = true)}>
                        <PlusIcon class="mr-2 size-4" />
                        Nuovo Post
                    </Button>
                </div>
            </Card.Header>
            <Card.Content>
                {#if committee.posts.length === 0}
                    <div
                        class="flex flex-col items-center justify-center py-8 text-center"
                    >
                        <p class="mb-2 text-sm font-medium">Nessun post</p>
                        <p class="text-sm text-muted-foreground">
                            Pubblica il primo post nella bacheca del comitato.
                        </p>
                    </div>
                {:else}
                    <div class="space-y-4">
                        {#each committee.posts as post}
                            <div class="rounded-lg border p-4">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h4 class="font-semibold">
                                            {post.title}
                                        </h4>
                                        <div
                                            class="mt-1 flex items-center gap-2 text-sm text-muted-foreground"
                                        >
                                            <span>{post.author.name}</span>
                                            <span>•</span>
                                            <span>
                                                {formatDistanceToNow(
                                                    new Date(post.created_at),
                                                    {
                                                        addSuffix: true,
                                                        locale: it,
                                                    },
                                                )}
                                            </span>
                                        </div>
                                        <p
                                            class="mt-3 whitespace-pre-wrap text-sm"
                                        >
                                            {post.content}
                                        </p>
                                    </div>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        onclick={() =>
                                            handleDeletePost(post.id)}
                                    >
                                        <TrashIcon
                                            class="size-4 text-destructive"
                                        />
                                    </Button>
                                </div>
                            </div>
                        {/each}
                    </div>
                {/if}
            </Card.Content>
        </Card.Root>
    </div>

    <!-- Add Member Dialog -->
    <Dialog.Root bind:open={addMemberDialogOpen}>
        <Dialog.Content>
            <Dialog.Header>
                <Dialog.Title>Aggiungi Socio al Comitato</Dialog.Title>
                <Dialog.Description>
                    Seleziona un socio da aggiungere al comitato.
                </Dialog.Description>
            </Dialog.Header>

            <form
                onsubmit={(e) => {
                    e.preventDefault();
                    handleAddMember();
                }}
                class="space-y-4"
            >
                <div class="space-y-2">
                    <Label>Socio <span class="text-red-500">*</span></Label>
                    <div class="relative">
                        <Popover.Root bind:open={openCombobox}>
                            <Popover.Trigger>
                                {#snippet child({ props })}
                                    <Button
                                        variant="outline"
                                        role="combobox"
                                        {...props}
                                        class="w-full justify-between"
                                    >
                                        {selectedMemberId
                                            ? availableMembers.find(
                                                  (m) =>
                                                      m.id === selectedMemberId,
                                              )?.name
                                            : "Seleziona socio..."}
                                        <SelectorIcon
                                            class="ml-2 size-4 shrink-0 opacity-50"
                                        />
                                    </Button>
                                {/snippet}
                            </Popover.Trigger>
                            <Popover.Content
                                class="w-[--bits-popover-anchor-width] p-0"
                            >
                                <Command.Root>
                                    <Command.Input
                                        placeholder="Cerca socio..."
                                    />
                                    <Command.List>
                                        <Command.Empty
                                            >Nessun socio trovato.</Command.Empty
                                        >
                                        <Command.Group>
                                            {#each availableMembers as member}
                                                <Command.Item
                                                    value={member.name}
                                                    onSelect={() => {
                                                        selectedMemberId =
                                                            member.id;
                                                        openCombobox = false;
                                                    }}
                                                >
                                                    <CheckIcon
                                                        class={cn(
                                                            "mr-2 size-4",
                                                            selectedMemberId ===
                                                                member.id
                                                                ? "opacity-100"
                                                                : "opacity-0",
                                                        )}
                                                    />
                                                    {member.name}
                                                    {#if member.email}
                                                        <span
                                                            class="ml-2 text-xs text-muted-foreground"
                                                        >
                                                            {member.email}
                                                        </span>
                                                    {/if}
                                                </Command.Item>
                                            {/each}
                                        </Command.Group>
                                    </Command.List>
                                </Command.Root>
                            </Popover.Content>
                        </Popover.Root>
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="role">Ruolo (opzionale)</Label>
                    <Input
                        id="role"
                        type="text"
                        bind:value={memberRole}
                        placeholder="es. Coordinatore, Membro, Segretario..."
                    />
                </div>

                <Dialog.Footer>
                    <Button
                        type="button"
                        variant="outline"
                        onclick={() => (addMemberDialogOpen = false)}
                    >
                        Annulla
                    </Button>
                    <Button type="submit" disabled={!selectedMemberId}
                        >Aggiungi</Button
                    >
                </Dialog.Footer>
            </form>
        </Dialog.Content>
    </Dialog.Root>

    <!-- Create Post Dialog -->
    <Dialog.Root bind:open={createPostDialogOpen}>
        <Dialog.Content>
            <Dialog.Header>
                <Dialog.Title>Nuovo Post</Dialog.Title>
                <Dialog.Description>
                    Pubblica un nuovo post nella bacheca del comitato.
                </Dialog.Description>
            </Dialog.Header>

            <form
                onsubmit={(e) => {
                    e.preventDefault();
                    handleCreatePost();
                }}
                class="space-y-4"
            >
                <div class="space-y-2">
                    <Label for="title"
                        >Titolo <span class="text-red-500">*</span></Label
                    >
                    <Input
                        id="title"
                        type="text"
                        bind:value={postFormData.title}
                        placeholder="Titolo del post..."
                        required
                    />
                </div>

                <div class="space-y-2">
                    <Label for="content"
                        >Contenuto <span class="text-red-500">*</span></Label
                    >
                    <Textarea
                        id="content"
                        bind:value={postFormData.content}
                        placeholder="Scrivi il contenuto del post..."
                        rows={6}
                        required
                    />
                </div>

                <Dialog.Footer>
                    <Button
                        type="button"
                        variant="outline"
                        onclick={() => (createPostDialogOpen = false)}
                    >
                        Annulla
                    </Button>
                    <Button type="submit">Pubblica</Button>
                </Dialog.Footer>
            </form>
        </Dialog.Content>
    </Dialog.Root>

    <!-- Confirm Member Removal Dialog -->
    <Dialog.Root bind:open={confirmRemovalOpen}>
        <Dialog.Content>
            <Dialog.Header>
                <Dialog.Title>Rimuovi Membro</Dialog.Title>
                <Dialog.Description>
                    Sei sicuro di voler rimuovere <strong
                        >{memberToRemoveName}</strong
                    > dal comitato? Questa azione non può essere annullata.
                </Dialog.Description>
            </Dialog.Header>
            <Dialog.Footer>
                <Button
                    variant="outline"
                    onclick={() => (confirmRemovalOpen = false)}
                >
                    Annulla
                </Button>
                <Button variant="destructive" onclick={confirmRemoveMember}>
                    Rimuovi
                </Button>
            </Dialog.Footer>
        </Dialog.Content>
    </Dialog.Root>

    <!-- Confirm Post Deletion Dialog -->
    <Dialog.Root bind:open={confirmPostDeletionOpen}>
        <Dialog.Content>
            <Dialog.Header>
                <Dialog.Title>Elimina Post</Dialog.Title>
                <Dialog.Description>
                    Sei sicuro di voler eliminare questo post dalla bacheca?
                    Questa azione non può essere annullata.
                </Dialog.Description>
            </Dialog.Header>
            <Dialog.Footer>
                <Button
                    variant="outline"
                    onclick={() => (confirmPostDeletionOpen = false)}
                >
                    Annulla
                </Button>
                <Button variant="destructive" onclick={confirmDeletePost}>
                    Elimina
                </Button>
            </Dialog.Footer>
        </Dialog.Content>
    </Dialog.Root>

    <!-- Confirm Image Deletion Dialog -->
    <Dialog.Root bind:open={confirmImageDeletionOpen}>
        <Dialog.Content>
            <Dialog.Header>
                <Dialog.Title>Rimuovi Logo Comitato</Dialog.Title>
                <Dialog.Description>
                    Sei sicuro di voler rimuovere il logo del comitato? Questa
                    azione non può essere annullata.
                </Dialog.Description>
            </Dialog.Header>
            <Dialog.Footer>
                <Button
                    variant="outline"
                    onclick={() => (confirmImageDeletionOpen = false)}
                >
                    Annulla
                </Button>
                <Button variant="destructive" onclick={confirmDeleteImage}>
                    Rimuovi
                </Button>
            </Dialog.Footer>
        </Dialog.Content>
    </Dialog.Root>
</AdminLayout>
