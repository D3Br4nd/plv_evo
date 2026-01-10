<script>
    import AdminLayout from "../../layouts/AdminLayout.svelte";
    import { Button } from "@/lib/components/ui/button";
    import { router } from "@inertiajs/svelte";
    import * as Card from "@/lib/components/ui/card";
    import * as Table from "@/lib/components/ui/table";
    let { stats, activity } = $props();

    function go(url) {
        if (!url) return;
        router.get(url, {}, { preserveScroll: true });
    }
</script>

<AdminLayout title="Dashboard">
    <div class="space-y-6">
        {#snippet headerActions()}
            <Button
                variant="outline"
                onclick={() => router.get("/admin/members")}
            >
                Vai ai soci
            </Button>
        {/snippet}

        <p class="text-sm text-muted-foreground">
            Benvenuto nel pannello amministrativo Pro Loco Venticanese
        </p>

        <!-- Stats Grid -->
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <Card.Root>
                <Card.Header
                    class="flex flex-row items-center justify-between space-y-0 pb-2"
                >
                    <Card.Title
                        class="text-xs font-medium uppercase tracking-wider text-muted-foreground"
                        >Soci Totali</Card.Title
                    >
                </Card.Header>
                <Card.Content>
                    <div class="text-3xl font-bold tracking-tight">
                        {stats?.membersTotal ?? 0}
                    </div>
                    <p class="text-xs text-muted-foreground mt-1">
                        Attivi ({new Date().getFullYear()}):
                        <span class="font-medium text-foreground"
                            >{stats?.membersActive ?? 0}</span
                        >
                    </p>
                </Card.Content>
            </Card.Root>

            <Card.Root>
                <Card.Header
                    class="flex flex-row items-center justify-between space-y-0 pb-2"
                >
                    <Card.Title
                        class="text-xs font-medium uppercase tracking-wider text-muted-foreground"
                        >Eventi</Card.Title
                    >
                </Card.Header>
                <Card.Content>
                    <div class="text-3xl font-bold tracking-tight">
                        {stats?.eventsTotal ?? 0}
                    </div>
                    <p class="text-xs text-muted-foreground mt-1">
                        In programma: <span class="font-medium text-foreground"
                            >{stats?.eventsUpcoming ?? 0}</span
                        >
                    </p>
                </Card.Content>
            </Card.Root>

            <Card.Root class="lg:col-span-1">
                <Card.Header
                    class="flex flex-row items-center justify-between space-y-0 pb-2"
                >
                    <Card.Title
                        class="text-xs font-medium uppercase tracking-wider text-muted-foreground"
                        >Comitati & Soci</Card.Title
                    >
                </Card.Header>
                <Card.Content class="pb-3 pt-1">
                    <div class="space-y-3">
                        {#if stats?.committeeStats?.length}
                            {#each stats.committeeStats.slice(0, 3) as comm}
                                <div class="space-y-1">
                                    <div
                                        class="flex items-center justify-between text-xs"
                                    >
                                        <span class="truncate font-medium"
                                            >{comm.name}</span
                                        >
                                        <span class="text-muted-foreground"
                                            >{comm.count}</span
                                        >
                                    </div>
                                    <div
                                        class="h-1.5 w-full overflow-hidden rounded-full bg-muted"
                                    >
                                        <div
                                            class="h-full bg-primary transition-all"
                                            style="width: {Math.min(
                                                100,
                                                (comm.count /
                                                    (stats.membersTotal || 1)) *
                                                    100,
                                            )}%"
                                        ></div>
                                    </div>
                                </div>
                            {/each}
                        {:else}
                            <p
                                class="py-2 text-xs text-muted-foreground italic"
                            >
                                Nessun comitato attivo
                            </p>
                        {/if}
                    </div>
                </Card.Content>
            </Card.Root>

            <Card.Root>
                <Card.Header
                    class="flex flex-row items-center justify-between space-y-0 pb-2"
                >
                    <Card.Title
                        class="text-xs font-medium uppercase tracking-wider text-muted-foreground"
                        >Notifiche ({new Date().getFullYear()})</Card.Title
                    >
                </Card.Header>
                <Card.Content>
                    <div class="flex items-end gap-2">
                        <div class="text-3xl font-bold tracking-tight">
                            {stats?.notificationStats?.sent ?? 0}
                        </div>
                        <div class="mb-1 text-xs text-muted-foreground">
                            Inviate
                        </div>
                    </div>
                    <div class="mt-2 flex items-center gap-2">
                        <div
                            class="h-2 flex-1 overflow-hidden rounded-full bg-muted"
                        >
                            <div
                                class="h-full bg-primary"
                                style="width: {stats?.notificationStats?.sent >
                                0
                                    ? (stats?.notificationStats?.read /
                                          stats?.notificationStats?.sent) *
                                      100
                                    : 0}%"
                            ></div>
                        </div>
                        <span
                            class="text-[10px] font-medium text-muted-foreground whitespace-nowrap"
                        >
                            {stats?.notificationStats?.read ?? 0} Lette
                        </span>
                    </div>
                </Card.Content>
            </Card.Root>
        </div>

        <Card.Root>
            <Card.Header class="pb-3">
                <Card.Title>Attività recenti</Card.Title>
                <Card.Description>
                    Operazioni su soci, eventi, comitati e contenuti.
                </Card.Description>
            </Card.Header>
            <Card.Content class="p-0">
                <div class="overflow-x-auto">
                    <Table.Root>
                        <Table.Header class="bg-muted/50 border-y">
                            <Table.Row>
                                <Table.Head
                                    class="h-10 text-xs font-semibold uppercase tracking-wider"
                                    >Quando</Table.Head
                                >
                                <Table.Head
                                    class="h-10 text-xs font-semibold uppercase tracking-wider"
                                    >Chi</Table.Head
                                >
                                <Table.Head
                                    class="h-10 text-xs font-semibold uppercase tracking-wider"
                                    >Cosa</Table.Head
                                >
                            </Table.Row>
                        </Table.Header>
                        <Table.Body>
                            {#if activity?.data?.length}
                                {#each activity.data as a (a.id)}
                                    <Table.Row
                                        class="hover:bg-muted/30 transition-colors"
                                    >
                                        <Table.Cell
                                            class="py-3 text-xs text-muted-foreground whitespace-nowrap"
                                        >
                                            {new Date(
                                                a.created_at,
                                            ).toLocaleString("it-IT", {
                                                day: "2-digit",
                                                month: "2-digit",
                                                year: "numeric",
                                                hour: "2-digit",
                                                minute: "2-digit",
                                            })}
                                        </Table.Cell>
                                        <Table.Cell
                                            class="py-3 text-sm font-medium"
                                        >
                                            {a.actor?.name || "Sistema"}
                                        </Table.Cell>
                                        <Table.Cell
                                            class="py-3 text-sm text-muted-foreground"
                                        >
                                            {a.summary}
                                        </Table.Cell>
                                    </Table.Row>
                                {/each}
                            {:else}
                                <Table.Row>
                                    <Table.Cell
                                        colspan="3"
                                        class="h-24 text-center text-sm text-muted-foreground"
                                    >
                                        Nessuna attività da mostrare.
                                    </Table.Cell>
                                </Table.Row>
                            {/if}
                        </Table.Body>
                    </Table.Root>
                </div>
            </Card.Content>
            {#if activity?.total > 0}
                <Card.Content
                    class="flex items-center justify-between gap-3 border-t px-4 py-3"
                >
                    <div class="text-xs text-muted-foreground">
                        Mostrati {activity?.from || 0}-{activity?.to || 0} di
                        {activity?.total || 0}
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="text-xs font-medium">
                            Pagina {activity?.current_page} di {activity?.last_page}
                        </div>
                        <div class="flex items-center gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                class="h-8"
                                disabled={activity?.current_page <= 1}
                                onclick={() => go(activity?.prev_page_url)}
                            >
                                <span class="hidden sm:inline">Precedente</span>
                                <span class="sm:hidden">&larr;</span>
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                class="h-8"
                                disabled={activity?.current_page >=
                                    activity?.last_page}
                                onclick={() => go(activity?.next_page_url)}
                            >
                                <span class="hidden sm:inline">Successiva</span>
                                <span class="sm:hidden">&rarr;</span>
                            </Button>
                        </div>
                    </div>
                </Card.Content>
            {/if}
        </Card.Root>
    </div>
</AdminLayout>
