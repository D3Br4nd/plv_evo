<script>
    import AdminLayout from "@/layouts/AdminLayout.svelte";
    import { router } from "@inertiajs/svelte";
    import { page } from "@inertiajs/svelte";
    import { Button } from "@/lib/components/ui/button";
    import { Input } from "@/lib/components/ui/input";
    import * as Card from "@/lib/components/ui/card";
    import * as Table from "@/lib/components/ui/table";

    let { event, checkins } = $props();
    let flash = $derived($page.props.flash);

    let qr_code = $state("");
    let processing = $state(false);
    let headerTitle = $derived.by(() =>
        `Check-in · ${event?.title || ""}`.trim(),
    );

    function submit() {
        if (!qr_code.trim()) return;
        processing = true;
        router.post(
            `/admin/events/${event.id}/checkins`,
            { qr_code: qr_code.trim() },
            {
                preserveScroll: true,
                onSuccess: () => (qr_code = ""),
                onFinish: () => (processing = false),
            },
        );
    }
</script>

<AdminLayout title={headerTitle}>
    {#snippet headerActions()}
        <Button variant="outline" onclick={() => router.get("/admin/events")}>
            Torna agli eventi
        </Button>
        <Button
            variant="outline"
            href={`/admin/events/${event.id}/checkins/export`}
        >
            Esporta CSV
        </Button>
    {/snippet}

    <div class="space-y-6">
        <p class="text-sm text-muted-foreground">
            Evento: <span class="text-foreground">{event.title}</span>
        </p>

        <Card.Root>
            <Card.Header>
                <Card.Title>Registra presenza</Card.Title>
                <Card.Description>
                    Inserisci il codice UUID del socio (tramite scanner o
                    copia-incolla).
                </Card.Description>
            </Card.Header>
            <Card.Content class="space-y-3">
                <div class="flex gap-2">
                    <Input
                        bind:value={qr_code}
                        placeholder="UUID del socio..."
                        class="flex-1 font-mono text-xs"
                        onkeydown={(e) => e.key === "Enter" && submit()}
                    />
                    <Button onclick={submit} disabled={processing}>
                        Check-in
                    </Button>
                </div>

                {#if flash?.error}
                    <div class="text-sm text-destructive">{flash.error}</div>
                {/if}
                {#if flash?.success}
                    <div class="text-sm text-green-600 dark:text-green-400">
                        {flash.success}
                    </div>
                {/if}
            </Card.Content>
        </Card.Root>

        <Card.Root>
            <Card.Header
                class="flex-row items-center justify-between space-y-0"
            >
                <Card.Title class="text-base">Ultimi check-in</Card.Title>
                <div class="text-xs text-muted-foreground">
                    Totale: {checkins.total}
                </div>
            </Card.Header>
            <Card.Content class="p-0">
                <Table.Root>
                    <Table.Header>
                        <Table.Row>
                            <Table.Head>Quando</Table.Head>
                            <Table.Head>Socio</Table.Head>
                            <Table.Head>Email</Table.Head>
                            <Table.Head>Operatore</Table.Head>
                        </Table.Row>
                    </Table.Header>
                    <Table.Body>
                        {#each checkins.data as c (c.id)}
                            <Table.Row>
                                <Table.Cell class="text-muted-foreground">
                                    {new Date(c.checked_in_at).toLocaleString(
                                        "it-IT",
                                    )}
                                </Table.Cell>
                                <Table.Cell class="font-medium">
                                    {c.membership?.user?.name || "-"}
                                </Table.Cell>
                                <Table.Cell class="text-muted-foreground">
                                    {c.membership?.user?.email || "-"}
                                </Table.Cell>
                                <Table.Cell class="text-muted-foreground">
                                    {c.checked_in_by?.name || "-"}
                                </Table.Cell>
                            </Table.Row>
                        {/each}
                    </Table.Body>
                </Table.Root>
            </Card.Content>
        </Card.Root>
    </div>
</AdminLayout>
