<script>
    import AdminLayout from "@/layouts/AdminLayout.svelte";
    import { router } from "@inertiajs/svelte";
    import { Button } from "@/lib/components/ui/button";
    import { Badge } from "@/lib/components/ui/badge";
    import * as Card from "@/lib/components/ui/card";
    import * as Table from "@/lib/components/ui/table";
    import * as AlertDialog from "@/lib/components/ui/alert-dialog";
    import PlusIcon from "@tabler/icons-svelte/icons/plus";
    import PencilIcon from "@tabler/icons-svelte/icons/pencil";
    import TrashIcon from "@tabler/icons-svelte/icons/trash";
    import EyeIcon from "@tabler/icons-svelte/icons/eye";

    let { pages } = $props();

    let pageToDelete = $state(null);

    function confirmDelete(page) {
        pageToDelete = page;
    }

    function doDelete() {
        if (!pageToDelete) return;
        router.delete(`/admin/content-pages/${pageToDelete.id}`, {
            preserveScroll: true,
            onFinish: () => {
                pageToDelete = null;
            },
        });
    }
</script>

<AdminLayout title="Contenuti">
    <div class="space-y-6">
        <div
            class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between"
        >
            <div>
                <h1 class="text-3xl font-bold tracking-tight">
                    Pagine del Sito
                </h1>
                <p class="text-sm text-muted-foreground">
                    Gestisci i contenuti informativi e le pagine pubbliche
                    pubblicabili su <code>/p/{"{"}slug{"}"}</code>.
                </p>
            </div>
            <Button href="/admin/content-pages/create" class="shadow-sm">
                <PlusIcon class="mr-2 size-4" />
                Nuova Pagina
            </Button>
        </div>

        <Card.Root>
            <Card.Content class="p-0">
                <Table.Root>
                    <Table.Header class="bg-muted/50">
                        <Table.Row>
                            <Table.Head>Titolo</Table.Head>
                            <Table.Head>Slug</Table.Head>
                            <Table.Head>Stato</Table.Head>
                            <Table.Head class="text-right">Azioni</Table.Head>
                        </Table.Row>
                    </Table.Header>
                    <Table.Body>
                        {#if pages.data.length === 0}
                            <Table.Row>
                                <Table.Cell
                                    colspan="4"
                                    class="h-24 text-center text-muted-foreground"
                                >
                                    Nessuna pagina trovata.
                                </Table.Cell>
                            </Table.Row>
                        {/if}
                        {#each pages.data as p (p.id)}
                            <Table.Row>
                                <Table.Cell class="font-medium">
                                    <div class="space-y-1">
                                        <div class="truncate max-w-[300px]">
                                            {p.title}
                                        </div>
                                        {#if p.excerpt}
                                            <div
                                                class="text-xs text-muted-foreground truncate max-w-[300px]"
                                            >
                                                {p.excerpt}
                                            </div>
                                        {/if}
                                    </div>
                                </Table.Cell>
                                <Table.Cell
                                    class="font-mono text-xs text-muted-foreground"
                                >
                                    /p/{p.slug}
                                </Table.Cell>
                                <Table.Cell>
                                    {#if p.status === "published"}
                                        <Badge>Pubblicata</Badge>
                                    {:else}
                                        <Badge variant="secondary">Bozza</Badge>
                                    {/if}
                                </Table.Cell>
                                <Table.Cell class="text-right">
                                    <div class="flex justify-end gap-1">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            href={`/p/${p.slug}`}
                                            target="_blank"
                                            title="Visualizza"
                                        >
                                            <EyeIcon class="size-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            href={`/admin/content-pages/${p.id}/edit`}
                                            title="Modifica"
                                        >
                                            <PencilIcon class="size-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            onclick={() => confirmDelete(p)}
                                            title="Elimina"
                                            class="text-destructive hover:text-destructive"
                                        >
                                            <TrashIcon class="size-4" />
                                        </Button>
                                    </div>
                                </Table.Cell>
                            </Table.Row>
                        {/each}
                    </Table.Body>
                </Table.Root>
            </Card.Content>
        </Card.Root>
    </div>

    <AlertDialog.Root open={pageToDelete !== null}>
        <AlertDialog.Content>
            <AlertDialog.Header>
                <AlertDialog.Title>Conferma Eliminazione</AlertDialog.Title>
                <AlertDialog.Description>
                    Sei sicuro di voler eliminare la pagina "<strong
                        >{pageToDelete?.title}</strong
                    >"? Questa azione non può essere annullata.
                </AlertDialog.Description>
            </AlertDialog.Header>
            <AlertDialog.Footer>
                <AlertDialog.Cancel onclick={() => (pageToDelete = null)}
                    >Annulla</AlertDialog.Cancel
                >
                <AlertDialog.Action
                    onclick={doDelete}
                    class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                >
                    Elimina
                </AlertDialog.Action>
            </AlertDialog.Footer>
        </AlertDialog.Content>
    </AlertDialog.Root>
</AdminLayout>
