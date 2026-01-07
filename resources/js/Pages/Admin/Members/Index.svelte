<script>
    /* eslint-disable */
    import { router, inertia } from "@inertiajs/svelte";
    import AdminLayout from "@/layouts/AdminLayout.svelte";
    import { page } from "@inertiajs/svelte";
    import {
        ChevronsLeft,
        ChevronLeft,
        ChevronRight,
        ChevronsRight,
        Users,
        UserCheck,
        UserX,
        Mail,
        Download,
        Upload,
        MoreVertical,
    } from "lucide-svelte";
    import { Button } from "@/lib/components/ui/button";
    import { Input } from "@/lib/components/ui/input";
    import { Badge } from "@/lib/components/ui/badge";
    import * as Card from "@/lib/components/ui/card";
    import * as Table from "@/lib/components/ui/table";
    import * as Dialog from "@/lib/components/ui/dialog";
    import * as Select from "@/lib/components/ui/select";
    import * as DropdownMenu from "@/lib/components/ui/dropdown-menu";
    import { Label } from "@/lib/components/ui/label";

    let { users, stats, chartData } = $props();
    let flash = $derived($page.props.flash);
    let canManageRoles = $derived($page.props.auth?.can?.manageRoles);
    let inviteUrl = $derived($page.props?.flash?.invite_url);
    let importErrors = $derived($page.props?.flash?.import_errors);

    let search = $state("");
    let perPage = $state("20");
    let isDeleteMemberOpen = $state(false);
    let deleteConfirmationUserId = $state(null);
    let processing = $state(false);
    let isImportDialogOpen = $state(false);
    let importFile = $state(null);
    let fileInputElement = $state(null);

    function isActiveByExpiry(user) {
        if (!user?.plv_expires_at) return false;
        const d = new Date(user.plv_expires_at);
        if (Number.isNaN(d.getTime())) return false;
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        return d >= today;
    }

    // Keep local search input in sync with server-provided filters (Inertia reactive props).
    $effect(() => {
        search = $page.props?.filters?.search || "";
        perPage = `${$page.props?.filters?.per_page || users?.per_page || 20}`;
    });

    // Debounce search
    let timer;
    function handleSearch(e) {
        clearTimeout(timer);
        timer = setTimeout(() => {
            router.get(
                "/admin/members",
                { search: search, per_page: perPage, page: 1 },
                { preserveState: true, replace: true },
            );
        }, 300);
    }

    function goToPage(pageNumber) {
        router.get(
            "/admin/members",
            { search: search, per_page: perPage, page: pageNumber },
            { preserveState: true, replace: true },
        );
    }

    function setRowsPerPage(v) {
        perPage = v;
        router.get(
            "/admin/members",
            { search: search, per_page: perPage, page: 1 },
            { preserveState: true, replace: true },
        );
    }

    function openDeleteModal(userId) {
        deleteConfirmationUserId = userId;
        isDeleteMemberOpen = true;
    }

    function closeDeleteModal() {
        deleteConfirmationUserId = null;
        isDeleteMemberOpen = false;
    }

    // If the dialog is closed via overlay/escape, keep the selected user id in sync.
    $effect(() => {
        if (!isDeleteMemberOpen && deleteConfirmationUserId) {
            closeDeleteModal();
        }
    });

    function deleteMember() {
        if (!deleteConfirmationUserId) return;

        processing = true;
        router.delete(`/admin/members/${deleteConfirmationUserId}`, {
            onFinish: () => {
                processing = false;
                closeDeleteModal();
            },
        });
    }

    function updateUserRole(userId, role) {
        router.patch(
            `/admin/members/${userId}/role`,
            { role },
            { preserveScroll: true, preserveState: true },
        );
    }

    function roleLabel(role) {
        switch (role) {
            case "super_admin":
                return "Super Admin";
            case "admin":
                return "Admin";
            case "member":
                return "Socio";
            default:
                return role || "-";
        }
    }

    function handleExport() {
        window.location.href = "/admin/members/export";
    }

    function openImportDialog() {
        isImportDialogOpen = true;
    }

    function closeImportDialog() {
        isImportDialogOpen = false;
        importFile = null;
        if (fileInputElement) {
            fileInputElement.value = "";
        }
    }

    function handleImport() {
        if (!importFile) return;

        const formData = new FormData();
        formData.append("file", importFile);

        processing = true;
        router.post("/admin/members/import", formData, {
            onFinish: () => {
                processing = false;
                closeImportDialog();
            },
        });
    }

    function handleFileChange(e) {
        const files = e.target.files;
        if (files && files.length > 0) {
            importFile = files[0];
        }
    }
</script>

<AdminLayout title="Soci">
    <div class="@container/main space-y-6">
        <p class="text-sm text-muted-foreground">
            Gestisci i membri dell'associazione.
        </p>

        {#if flash?.success}
            <div class="text-sm text-green-600 dark:text-green-400">
                {flash.success}
            </div>
        {/if}
        {#if inviteUrl}
            <div class="text-sm text-muted-foreground">
                Link invito (copia e invia al socio se necessario):
                <span class="font-mono break-all">{inviteUrl}</span>
            </div>
        {/if}
        {#if flash?.error}
            <div class="text-sm text-destructive">{flash.error}</div>
        {/if}

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Card.Root>
                <Card.Header
                    class="flex flex-row items-center justify-between space-y-0 pb-2"
                >
                    <Card.Title class="text-sm font-medium"
                        >Totale soci</Card.Title
                    >
                    <Users class="h-4 w-4 text-muted-foreground" />
                </Card.Header>
                <Card.Content>
                    <div class="text-2xl font-bold">
                        {stats?.total ?? users.total ?? 0}
                    </div>
                </Card.Content>
            </Card.Root>

            <Card.Root>
                <Card.Header
                    class="flex flex-row items-center justify-between space-y-0 pb-2"
                >
                    <Card.Title class="text-sm font-medium">Attivi</Card.Title>
                    <UserCheck class="h-4 w-4 text-muted-foreground" />
                </Card.Header>
                <Card.Content>
                    <div class="text-2xl font-bold">{stats?.active ?? 0}</div>
                </Card.Content>
            </Card.Root>

            <Card.Root>
                <Card.Header
                    class="flex flex-row items-center justify-between space-y-0 pb-2"
                >
                    <Card.Title class="text-sm font-medium">Scaduti</Card.Title>
                    <UserX class="h-4 w-4 text-muted-foreground" />
                </Card.Header>
                <Card.Content>
                    <div class="text-2xl font-bold">{stats?.expired ?? 0}</div>
                </Card.Content>
            </Card.Root>

            <Card.Root>
                <Card.Header
                    class="flex flex-row items-center justify-between space-y-0 pb-2"
                >
                    <Card.Title class="text-sm font-medium"
                        >Inviti in attesa</Card.Title
                    >
                    <Mail class="h-4 w-4 text-muted-foreground" />
                </Card.Header>
                <Card.Content>
                    <div class="text-2xl font-bold">
                        {stats?.pendingInvites ?? 0}
                    </div>
                </Card.Content>
            </Card.Root>
        </div>

        <!-- Filters -->
        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"
        >
            <div class="w-full max-w-md">
                <Label for="member-search" class="text-sm font-medium"
                    >Cerca</Label
                >
                <Input
                    id="member-search"
                    type="text"
                    bind:value={search}
                    oninput={handleSearch}
                    placeholder="Nome o email..."
                    class="mt-2"
                />
            </div>
            <div class="flex gap-2">
                <DropdownMenu.Root>
                    <DropdownMenu.Trigger>
                        <Button variant="outline">
                            <MoreVertical class="h-4 w-4 mr-2" />
                            Azioni
                        </Button>
                    </DropdownMenu.Trigger>
                    <DropdownMenu.Content align="end">
                        <DropdownMenu.Item onclick={handleExport}>
                            <Download class="h-4 w-4 mr-2" />
                            Esporta CSV
                        </DropdownMenu.Item>
                        <DropdownMenu.Item onclick={openImportDialog}>
                            <Upload class="h-4 w-4 mr-2" />
                            Importa CSV
                        </DropdownMenu.Item>
                    </DropdownMenu.Content>
                </DropdownMenu.Root>
                <Button onclick={() => router.get("/admin/members/create")}
                    >Nuovo socio</Button
                >
            </div>
        </div>

        {#if importErrors && importErrors.length > 0}
            <Card.Root class="border-amber-200 dark:border-amber-800">
                <Card.Header>
                    <Card.Title class="text-amber-800 dark:text-amber-200"
                        >Errori Import CSV</Card.Title
                    >
                    <Card.Description
                        >Alcune righe non sono state importate a causa di errori
                        di validazione.</Card.Description
                    >
                </Card.Header>
                <Card.Content>
                    <ul class="list-disc list-inside space-y-1 text-sm">
                        {#each importErrors as error}
                            <li class="text-muted-foreground">{error}</li>
                        {/each}
                    </ul>
                </Card.Content>
            </Card.Root>
        {/if}

        <!-- Table -->
        <!-- NOTE: Card.Root has default padding (py-6) and gap (gap-6). For tables we want a flush container. -->
        <Card.Root class="overflow-hidden p-0 gap-0">
            <Card.Content class="p-0">
                <Table.Root>
                    <Table.Header class="bg-muted">
                        <Table.Row>
                            <Table.Head>Nome</Table.Head>
                            <Table.Head>Ruolo Pro Loco</Table.Head>
                            <Table.Head>Status</Table.Head>
                            <Table.Head>Scadenza</Table.Head>
                            <Table.Head class="text-right">Azioni</Table.Head>
                        </Table.Row>
                    </Table.Header>
                    <Table.Body>
                        {#if users.data?.length}
                            {#each users.data as user (user.id)}
                                <Table.Row>
                                    <Table.Cell class="font-medium">
                                        <div class="flex items-center gap-3">
                                            {#if user.avatar_url}
                                                <img
                                                    src={user.avatar_url}
                                                    alt={user.name}
                                                    class="h-8 w-8 rounded-full object-cover border bg-background"
                                                    loading="lazy"
                                                />
                                            {:else}
                                                <div
                                                    class="flex h-8 w-8 items-center justify-center rounded-full bg-secondary text-secondary-foreground text-xs font-semibold"
                                                >
                                                    {user.name
                                                        ?.charAt(0)
                                                        ?.toUpperCase?.() ||
                                                        "U"}
                                                </div>
                                            {/if}
                                            <div class="min-w-0">
                                                <div class="truncate">
                                                    {user.name}
                                                </div>
                                                <div
                                                    class="truncate text-xs text-muted-foreground"
                                                >
                                                    {user.email}
                                                </div>
                                            </div>
                                        </div>
                                    </Table.Cell>
                                    <Table.Cell class="text-sm">
                                        {#if user.plv_role}
                                            <Badge
                                                variant="secondary"
                                                class="bg-blue-50 text-blue-700 hover:bg-blue-100 border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-800"
                                            >
                                                {user.plv_role
                                                    .toLowerCase()
                                                    .replace(/^\w/, (c) =>
                                                        c.toUpperCase(),
                                                    )}
                                            </Badge>
                                        {:else}
                                            Socio
                                        {/if}
                                    </Table.Cell>
                                    <Table.Cell>
                                        {#if isActiveByExpiry(user) || (user.memberships && user.memberships.length > 0)}
                                            <Badge
                                                variant="secondary"
                                                class="bg-green-100 text-green-800 hover:bg-green-100 dark:bg-green-900 dark:text-green-300"
                                                >Attivo</Badge
                                            >
                                        {:else}
                                            <Badge
                                                variant="outline"
                                                class="text-muted-foreground"
                                                >Scaduto</Badge
                                            >
                                        {/if}
                                    </Table.Cell>
                                    <Table.Cell
                                        class="text-sm text-muted-foreground"
                                    >
                                        {#if user.plv_expires_at}
                                            {new Date(
                                                user.plv_expires_at,
                                            ).toLocaleDateString("it-IT")}
                                        {:else}
                                            -
                                        {/if}
                                    </Table.Cell>
                                    <Table.Cell class="text-right">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onclick={() =>
                                                router.get(
                                                    `/admin/members/${user.id}`,
                                                )}
                                            class="mr-2"
                                        >
                                            Apri
                                        </Button>
                                        <Button
                                            variant="destructive"
                                            size="sm"
                                            onclick={() =>
                                                openDeleteModal(user.id)}
                                        >
                                            Elimina
                                        </Button>
                                    </Table.Cell>
                                </Table.Row>
                            {/each}
                        {:else}
                            <Table.Row>
                                <Table.Cell
                                    colspan={5}
                                    class="h-24 text-center text-muted-foreground"
                                >
                                    Nessun risultato.
                                </Table.Cell>
                            </Table.Row>
                        {/if}
                    </Table.Body>
                </Table.Root>
            </Card.Content>

            <!-- Pagination -->
            <div
                class="flex items-center justify-between px-4 py-3 border-t border-border"
            >
                <div class="text-xs text-muted-foreground hidden sm:block">
                    Membri {users.from || 0} - {users.to || 0} di {users.total}
                </div>
                <div class="flex w-full items-center gap-6 sm:w-auto">
                    <div class="hidden items-center gap-2 sm:flex">
                        <Label for="rows-per-page" class="text-sm font-medium"
                            >Righe per pagina</Label
                        >
                        <Select.Root
                            type="single"
                            bind:value={perPage}
                            onValueChange={(v) => setRowsPerPage(v)}
                        >
                            <Select.Trigger
                                size="sm"
                                class="w-20"
                                id="rows-per-page"
                            >
                                {perPage}
                            </Select.Trigger>
                            <Select.Content side="top">
                                {#each ["10", "20", "30", "40", "50"] as p (p)}
                                    <Select.Item value={p}>{p}</Select.Item>
                                {/each}
                            </Select.Content>
                        </Select.Root>
                    </div>

                    <div
                        class="flex items-center justify-center text-sm font-medium"
                    >
                        Pagina {users.current_page} di {users.last_page}
                    </div>

                    <div class="ms-auto flex items-center gap-2 sm:ms-0">
                        <Button
                            variant="outline"
                            class="hidden h-8 w-8 p-0 sm:flex"
                            onclick={() => goToPage(1)}
                            disabled={users.current_page <= 1}
                        >
                            <span class="sr-only">Prima pagina</span>
                            <ChevronsLeft class="h-4 w-4" />
                        </Button>
                        <Button
                            variant="outline"
                            class="h-8 w-8 p-0"
                            onclick={() =>
                                goToPage(Math.max(1, users.current_page - 1))}
                            disabled={users.current_page <= 1}
                        >
                            <span class="sr-only">Pagina precedente</span>
                            <ChevronLeft class="h-4 w-4" />
                        </Button>
                        <Button
                            variant="outline"
                            class="h-8 w-8 p-0"
                            onclick={() =>
                                goToPage(
                                    Math.min(
                                        users.last_page,
                                        users.current_page + 1,
                                    ),
                                )}
                            disabled={users.current_page >= users.last_page}
                        >
                            <span class="sr-only">Pagina successiva</span>
                            <ChevronRight class="h-4 w-4" />
                        </Button>
                        <Button
                            variant="outline"
                            class="hidden h-8 w-8 p-0 sm:flex"
                            onclick={() => goToPage(users.last_page)}
                            disabled={users.current_page >= users.last_page}
                        >
                            <span class="sr-only">Ultima pagina</span>
                            <ChevronsRight class="h-4 w-4" />
                        </Button>
                    </div>
                </div>
            </div>
        </Card.Root>
    </div>

    <Dialog.Root bind:open={isDeleteMemberOpen}>
        <Dialog.Content class="max-w-md">
            <Dialog.Header>
                <Dialog.Title>Elimina socio</Dialog.Title>
                <Dialog.Description>
                    Questa azione non può essere annullata.
                </Dialog.Description>
            </Dialog.Header>

            <Dialog.Footer class="mt-6">
                <Button
                    variant="outline"
                    onclick={closeDeleteModal}
                    disabled={processing}
                >
                    Annulla
                </Button>
                <Button
                    variant="destructive"
                    onclick={deleteMember}
                    disabled={processing}
                >
                    {processing ? "Eliminazione..." : "Elimina"}
                </Button>
            </Dialog.Footer>
        </Dialog.Content>
    </Dialog.Root>

    <!-- Import Dialog -->
    <Dialog.Root bind:open={isImportDialogOpen}>
        <Dialog.Content class="max-w-md">
            <Dialog.Header>
                <Dialog.Title>Importa Soci da CSV</Dialog.Title>
                <Dialog.Description>
                    Carica un file CSV con i dati dei soci. Il file deve
                    contenere almeno le colonne "Nome" ed "Email".
                </Dialog.Description>
            </Dialog.Header>

            <div class="space-y-4 py-4">
                <div class="space-y-2">
                    <Label for="csv-file">File CSV</Label>
                    <input
                        id="csv-file"
                        type="file"
                        accept=".csv,text/csv"
                        bind:this={fileInputElement}
                        onchange={handleFileChange}
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    />
                    {#if importFile}
                        <p class="text-xs text-muted-foreground">
                            File selezionato: {importFile.name} ({Math.round(
                                importFile.size / 1024,
                            )} KB)
                        </p>
                    {/if}
                </div>

                <div class="rounded-md bg-muted p-3 text-sm space-y-1">
                    <p class="font-medium">Formato CSV:</p>
                    <ul class="list-disc list-inside text-xs space-y-0.5">
                        <li>Colonne obbligatorie: Nome, Email</li>
                        <li>
                            I soci esistenti (stessa email) verranno aggiornati
                        </li>
                        <li>I nuovi soci riceveranno un invito via email</li>
                        <li>Encoding: UTF-8</li>
                    </ul>
                </div>
            </div>

            <Dialog.Footer>
                <Button
                    variant="outline"
                    onclick={closeImportDialog}
                    disabled={processing}
                >
                    Annulla
                </Button>
                <Button
                    onclick={handleImport}
                    disabled={!importFile || processing}
                >
                    {processing ? "Importazione..." : "Importa"}
                </Button>
            </Dialog.Footer>
        </Dialog.Content>
    </Dialog.Root>
</AdminLayout>
