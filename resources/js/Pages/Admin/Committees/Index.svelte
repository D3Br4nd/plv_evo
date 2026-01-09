<script>
    import AdminLayout from "@/layouts/AdminLayout.svelte";
    import { Button } from "@/lib/components/ui/button";
    import * as Card from "@/lib/components/ui/card";
    import * as Dialog from "@/lib/components/ui/dialog";
    import { Input } from "@/lib/components/ui/input";
    import { Label } from "@/lib/components/ui/label";
    import { Textarea } from "@/lib/components/ui/textarea";
    import { router } from "@inertiajs/svelte";
    import PlusIcon from "@tabler/icons-svelte/icons/plus";
    import UsersIcon from "@tabler/icons-svelte/icons/users";
    import MessageCircleIcon from "@tabler/icons-svelte/icons/message-circle";

    let { committees = [] } = $props();

    let createDialogOpen = $state(false);
    let formData = $state({
        name: "",
        description: "",
        status: "active",
    });

    function handleCreateCommittee() {
        router.post("/admin/committees", formData, {
            onSuccess: () => {
                createDialogOpen = false;
                formData = { name: "", description: "", status: "active" };
            },
        });
    }
</script>

<AdminLayout title="Comitati">
    <div class="space-y-6">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <p class="text-sm text-muted-foreground">
                Gestisci i comitati della Pro Loco e i loro membri.
            </p>
            {#if committees.length > 0}
                <Button onclick={() => (createDialogOpen = true)}>
                    <PlusIcon class="mr-2 size-4" />
                    Crea Comitato
                </Button>
            {/if}
        </div>
        {#if committees.length === 0}
            <Card.Root>
                <Card.Content
                    class="flex flex-col items-center justify-center py-12"
                >
                    <UsersIcon class="mb-4 size-12 text-muted-foreground" />
                    <h3 class="mb-2 text-lg font-semibold">Nessun comitato</h3>
                    <p class="mb-4 text-sm text-muted-foreground">
                        Inizia creando il tuo primo comitato.
                    </p>
                    <Button onclick={() => (createDialogOpen = true)}>
                        <PlusIcon class="mr-2 size-4" />
                        Crea Comitato
                    </Button>
                </Card.Content>
            </Card.Root>
        {:else}
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                {#each committees as committee}
                    <Card.Root
                        class="hover:border-primary transition-colors cursor-pointer"
                    >
                        <a
                            href="/admin/committees/{committee.id}"
                            class="block"
                        >
                            <Card.Header>
                                <div
                                    class="mb-4 flex h-12 w-12 items-center justify-center overflow-hidden rounded-lg bg-muted"
                                >
                                    {#if committee.image_url}
                                        <img
                                            src={committee.image_url}
                                            alt={committee.name}
                                            class="h-full w-full object-cover"
                                        />
                                    {:else}
                                        <UsersIcon
                                            class="size-6 text-muted-foreground"
                                        />
                                    {/if}
                                </div>
                                <Card.Title>{committee.name}</Card.Title>
                                {#if committee.description}
                                    <Card.Description class="mt-2 line-clamp-2">
                                        {committee.description}
                                    </Card.Description>
                                {/if}
                            </Card.Header>
                            <Card.Content>
                                <div
                                    class="flex items-center gap-6 text-sm text-muted-foreground"
                                >
                                    <div class="flex items-center gap-2">
                                        <UsersIcon class="size-4" />
                                        <span
                                            >{committee.members_count}
                                            {committee.members_count === 1
                                                ? "membro"
                                                : "membri"}</span
                                        >
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <MessageCircleIcon class="size-4" />
                                        <span
                                            >{committee.posts_count}
                                            {committee.posts_count === 1
                                                ? "post"
                                                : "post"}</span
                                        >
                                    </div>
                                </div>
                            </Card.Content>
                            <Card.Footer>
                                <div
                                    class="flex items-center justify-between w-full"
                                >
                                    <span
                                        class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium {committee.status ===
                                        'active'
                                            ? 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20'
                                            : 'bg-gray-50 text-gray-600 ring-1 ring-inset ring-gray-500/20'}"
                                    >
                                        {committee.status === "active"
                                            ? "Attivo"
                                            : "Inattivo"}
                                    </span>
                                    {#if committee.creator}
                                        <span
                                            class="text-xs text-muted-foreground"
                                        >
                                            Creato da {committee.creator.name}
                                        </span>
                                    {/if}
                                </div>
                            </Card.Footer>
                        </a>
                    </Card.Root>
                {/each}
            </div>
        {/if}
    </div>

    <!-- Create Committee Dialog -->
    <Dialog.Root bind:open={createDialogOpen}>
        <Dialog.Content>
            <Dialog.Header>
                <Dialog.Title>Crea Nuovo Comitato</Dialog.Title>
                <Dialog.Description>
                    Aggiungi un nuovo comitato alla Pro Loco.
                </Dialog.Description>
            </Dialog.Header>

            <form
                onsubmit={(e) => {
                    e.preventDefault();
                    handleCreateCommittee();
                }}
                class="space-y-4"
            >
                <div class="space-y-2">
                    <Label for="name"
                        >Nome Comitato <span class="text-red-500">*</span
                        ></Label
                    >
                    <Input
                        id="name"
                        type="text"
                        bind:value={formData.name}
                        placeholder="es. Comitato Fiera"
                        required
                    />
                </div>

                <div class="space-y-2">
                    <Label for="description">Descrizione</Label>
                    <Textarea
                        id="description"
                        bind:value={formData.description}
                        placeholder="Descrizione del comitato..."
                        rows={4}
                    />
                </div>

                <Dialog.Footer>
                    <Button
                        type="button"
                        variant="outline"
                        onclick={() => (createDialogOpen = false)}
                    >
                        Annulla
                    </Button>
                    <Button type="submit">Crea Comitato</Button>
                </Dialog.Footer>
            </form>
        </Dialog.Content>
    </Dialog.Root>
</AdminLayout>
