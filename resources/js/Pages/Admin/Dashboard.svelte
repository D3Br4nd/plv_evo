<script>
    import AdminLayout from "../../layouts/AdminLayout.svelte";
    import { Button } from "@/lib/components/ui/button";
    import { Input } from "@/lib/components/ui/input";
    import { Label } from "@/lib/components/ui/label";
    import { router, page } from "@inertiajs/svelte";
    import * as Card from "@/lib/components/ui/card";
    import * as Table from "@/lib/components/ui/table";
    import * as Dialog from "@/lib/components/ui/dialog";
    import { Trash2 } from "lucide-svelte";
    
    let { stats, activity, recentActivity } = $props();
    
    let isSuperAdmin = $derived($page.props.auth?.user?.role === 'super_admin');
    let isCleanupDialogOpen = $state(false);
    let cleanupDate = $state('');
    let processing = $state(false);

    function go(url) {
        if (!url) return;
        router.get(url, {}, { preserveScroll: true });
    }
    
    function openCleanupDialog() {
        // Set default date to 30 days ago
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
        cleanupDate = thirtyDaysAgo.toISOString().split('T')[0];
        isCleanupDialogOpen = true;
    }
    
    function clearActivityLogs() {
        if (!cleanupDate) return;
        processing = true;
        router.delete('/admin/activity-logs/clear', {
            data: { before_date: cleanupDate },
            onFinish: () => {
                processing = false;
                isCleanupDialogOpen = false;
            },
        });
    }
</script>

<AdminLayout title="Dashboard">
    <div class="space-y-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Dashboard Amministrativa</h1>
                <p class="text-sm text-muted-foreground">
                    Benvenuto nel pannello gestionale della Pro Loco Venticanese.
                </p>
            </div>
            {#snippet headerActions()}
                <Button
                    variant="outline"
                    class="shadow-sm"
                    onclick={() => router.get("/admin/members")}
                >
                    Gestione Soci
                </Button>
            {/snippet}
        </div>

        <!-- Row 1: Main Stats (4 columns) -->
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
                        >Eventi ({new Date().getFullYear()})</Card.Title
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

            <Card.Root>
                <Card.Header
                    class="flex flex-row items-center justify-between space-y-0 pb-2"
                >
                    <Card.Title
                        class="text-xs font-medium uppercase tracking-wider text-muted-foreground"
                        >Progetti</Card.Title
                    >
                </Card.Header>
                <Card.Content>
                    <div class="text-3xl font-bold tracking-tight">
                        {stats?.projectsTotal ?? 0}
                    </div>
                    <div class="mt-2 flex items-center gap-2 text-[10px] font-medium">
                        <span class="text-muted-foreground">Da fare: <span class="text-foreground">{stats?.projectsTodo ?? 0}</span></span>
                        <span class="text-muted-foreground">•</span>
                        <span class="text-blue-600">In corso: {stats?.projectsInProgress ?? 0}</span>
                        <span class="text-muted-foreground">•</span>
                        <span class="text-emerald-600">Fatti: {stats?.projectsDone ?? 0}</span>
                    </div>
                </Card.Content>
            </Card.Root>

            <Card.Root>
                <Card.Header
                    class="flex flex-row items-center justify-between space-y-0 pb-2"
                >
                    <Card.Title
                        class="text-xs font-medium uppercase tracking-wider text-muted-foreground"
                        >Contenuti</Card.Title
                    >
                </Card.Header>
                <Card.Content>
                    <div class="text-3xl font-bold tracking-tight">
                        {stats?.contentPagesTotal ?? 0}
                    </div>
                    <p class="text-xs text-muted-foreground mt-1">
                        Pagine pubblicate
                    </p>
                </Card.Content>
            </Card.Root>
        </div>

        <!-- Row 2: Committees & Notifications (2 columns) -->
        <div class="grid gap-4 md:grid-cols-2">
            <Card.Root>
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
                            {#each stats.committeeStats as comm}
                                <div class="space-y-1">
                    <div
                                        class="flex items-center justify-between text-xs"
                                    >
                                        <span class="truncate font-medium"
                                            >{comm.name}</span
                                        >
                                        <span class="text-muted-foreground shrink-0 ml-2"
                                            >{comm.count}/{comm.total} ({comm.percentage}%)</span
                                        >
                                    </div>
                                    <div
                                        class="h-2 w-full overflow-hidden rounded-full bg-muted"
                                    >
                                        <div
                                            class="h-full transition-all"
                                            style="width: {comm.percentage}%; background-color: {comm.color}"
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

        <!-- Row 3: Visual Activity Overview -->
        <Card.Root>
            <Card.Header>
                <Card.Title class="text-base">Panoramica Attività</Card.Title>
                <Card.Description>Distribuzione delle azioni negli ultimi 7 giorni</Card.Description>
            </Card.Header>
            <Card.Content>
                {#if recentActivity?.length}
                    {@const last7Days = Array.from({length: 7}, (_, i) => {
                        const d = new Date();
                        d.setDate(d.getDate() - (6 - i));
                        return d;
                    })}
                    {@const activityByDay = last7Days.map(day => {
                        const dayStr = day.toISOString().split('T')[0];
                        const count = recentActivity.filter(a => a.created_at.startsWith(dayStr)).length;
                        return { day, count };
                    })}
                    {@const maxCount = Math.max(...activityByDay.map(d => d.count), 1)}
                    
                    <div class="flex items-end justify-between gap-2 h-32">
                        {#each activityByDay as {day, count}}
                            <div class="flex flex-col items-center gap-2 flex-1">
                                <div class="relative w-full bg-muted/30 rounded-t-lg border border-muted flex-1 flex items-end">
                                    <div 
                                        class="w-full bg-gradient-to-t from-primary/80 to-primary/40 rounded-t-lg transition-all hover:opacity-80"
                                        style="height: {count > 0 ? (count / maxCount) * 100 : 2}%"
                                        title="{count} attività"
                                    ></div>
                                </div>
                                <span class="text-[10px] text-muted-foreground font-medium">
                                    {day.toLocaleDateString('it-IT', { weekday: 'short' })}
                                </span>
                            </div>
                        {/each}
                    </div>
                {:else}
                    <div class="h-32 flex items-center justify-center text-sm text-muted-foreground">
                        Nessuna attività recente da visualizzare
                    </div>
                {/if}
            </Card.Content>
        </Card.Root>

        <Card.Root>
            <Card.Header class="pb-3">
                <div class="flex items-center justify-between">
                    <div>
                        <Card.Title>Attività recenti</Card.Title>
                        <Card.Description>
                            Operazioni su soci, eventi, comitati e contenuti.
                        </Card.Description>
                    </div>
                    {#if isSuperAdmin}
                        <Button
                            variant="outline"
                            size="sm"
                            class="gap-2"
                            onclick={openCleanupDialog}
                        >
                            <Trash2 class="size-4" />
                            <span class="hidden sm:inline">Pulisci vecchie attività</span>
                        </Button>
                    {/if}
                </div>
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
    
    <!-- Cleanup Activity Logs Dialog -->
    <Dialog.Root bind:open={isCleanupDialogOpen}>
        <Dialog.Content class="max-w-md">
            <Dialog.Header>
                <Dialog.Title>Pulisci Attività Vecchie</Dialog.Title>
                <Dialog.Description>
                    Elimina tutte le attività registrate prima della data selezionata. Questa azione non può essere annullata.
                </Dialog.Description>
            </Dialog.Header>
            
            <div class="space-y-4 py-4">
                <div class="space-y-2">
                    <Label for="cleanup-date">Elimina attività precedenti al:</Label>
                    <Input 
                        id="cleanup-date"
                        type="date" 
                        bind:value={cleanupDate}
                        max={new Date().toISOString().split('T')[0]}
                        class="w-full"
                    />
                    <p class="text-xs text-muted-foreground">
                        Verranno eliminate tutte le attività create prima di questa data.
                    </p>
                </div>
            </div>
            
            <Dialog.Footer>
                <Button 
                    variant="outline" 
                    onclick={() => isCleanupDialogOpen = false}
                    disabled={processing}
                >
                    Annulla
                </Button>
                <Button 
                    variant="destructive"
                    onclick={clearActivityLogs}
                    disabled={processing || !cleanupDate}
                >
                    {processing ? 'Eliminazione...' : 'Elimina Attività'}
                </Button>
            </Dialog.Footer>
        </Dialog.Content>
    </Dialog.Root>
</AdminLayout>
