<script>
    /* eslint-disable no-undef */
    import { page } from "@inertiajs/svelte";
    import { router } from "@inertiajs/svelte";
    import QRCode from "qrcode";

    import AdminLayout from "@/layouts/AdminLayout.svelte";
    import { Button } from "@/lib/components/ui/button";
    import { Input } from "@/lib/components/ui/input";
    import * as Card from "@/lib/components/ui/card";
    import { Switch } from "@/lib/components/ui/switch";
    import { Label } from "@/lib/components/ui/label";

    import italyPlaces from "@/data/italy_places.json";

    let { member, year } = $props();
    let flash = $derived($page.props.flash);
    let serverErrors = $derived($page.props.errors || {});
    let errorsLocal = $state({});

    let inviteUrl = $derived($page.props?.flash?.invite_url);

    let uuid = $derived(member?.id);
    let qrDataUrl = $state(null);
    let hydrated = $state(false);

    function dateOnly(v) {
        if (!v) return "";
        if (typeof v !== "string") return "";
        // Handles both "YYYY-MM-DD" and ISO strings like "YYYY-MM-DDT00:00:00.000000Z"
        return v.length >= 10 ? v.slice(0, 10) : v;
    }

    async function generateQr() {
        if (!uuid) return;
        qrDataUrl = await QRCode.toDataURL(uuid, {
            width: 220,
            margin: 2,
            color: { dark: "#000000", light: "#FFFFFF" },
        });
    }

    $effect(() => {
        generateQr();
    });

    let form = $state({
        name: "",
        first_name: "",
        last_name: "",
        email: "",
        phone: "",
        plv_role: "",
        role: "",

        birth_date: "",
        birth_place_type: "it",
        birth_province_code: "",
        birth_city: "",
        birth_country: "",

        residence_type: "it",
        residence_street: "",
        residence_house_number: "",
        residence_locality: "",
        residence_province_code: "",
        residence_city: "",
        residence_country: "",

        plv_joined_at: "",
    });

    // Keep a local copy of server validation errors, so we can clear them on user edits
    // (otherwise disabling the save button would deadlock resubmission).
    $effect(() => {
        errorsLocal = serverErrors || {};
    });

    // If user edits any field after a validation error, clear errors locally to allow resubmission.
    // Important: clear only on *actual* form changes (not on first run / hydration).
    let _hasFormSnapshot = false;
    let _lastFormSnapshot = "";
    $effect(() => {
        // Spreading reads each key, establishing reactive dependencies on all fields.
        const snapshot = JSON.stringify({ ...form });

        if (!_hasFormSnapshot) {
            _hasFormSnapshot = true;
            _lastFormSnapshot = snapshot;
            return;
        }

        if (snapshot === _lastFormSnapshot) return;
        _lastFormSnapshot = snapshot;

        if (Object.keys(errorsLocal).length > 0) errorsLocal = {};
    });

    // Sync form from server-provided member.
    // Do NOT overwrite local input if there are validation errors.
    $effect(() => {
        if (!member?.id) return;
        if (Object.keys(serverErrors).length > 0) return;

        form.name = member?.name ?? "";
        form.first_name = member?.first_name ?? "";
        form.last_name = member?.last_name ?? "";
        form.email = member?.email ?? "";
        form.phone = member?.phone ?? "";
        form.plv_role = member?.plv_role ?? "";
        form.role = member?.role ?? "member";

        form.birth_date = dateOnly(member?.birth_date ?? "");
        form.birth_place_type = member?.birth_place_type ?? "it";
        form.birth_province_code = member?.birth_province_code ?? "";
        form.birth_city = member?.birth_city ?? "";
        form.birth_country = member?.birth_country ?? "";

        form.residence_type = member?.residence_type ?? "it";
        form.residence_street = member?.residence_street ?? "";
        form.residence_house_number = member?.residence_house_number ?? "";
        form.residence_locality = member?.residence_locality ?? "";
        form.residence_province_code = member?.residence_province_code ?? "";
        form.residence_city = member?.residence_city ?? "";
        form.residence_country = member?.residence_country ?? "";

        form.plv_joined_at = dateOnly(member?.plv_joined_at ?? "");
        hydrated = true;
    });

    function addOneYear(dateStr) {
        if (!dateStr) return "";
        const d = new Date(dateStr + "T00:00:00");
        if (Number.isNaN(d.getTime())) return "";
        d.setFullYear(d.getFullYear() + 1);
        const yyyy = d.getFullYear();
        const mm = String(d.getMonth() + 1).padStart(2, "0");
        const dd = String(d.getDate()).padStart(2, "0");
        return `${yyyy}-${mm}-${dd}`;
    }

    let expiresPreview = $derived(addOneYear(form.plv_joined_at));

    let citiesForBirthProvince = $derived(
        italyPlaces.citiesByProvince?.[form.birth_province_code] ?? [],
    );
    let citiesForResidenceProvince = $derived(
        italyPlaces.citiesByProvince?.[form.residence_province_code] ?? [],
    );

    function save() {
        if (!member?.id) return;
        // Safety net: avoid sending empty strings for required fields if user clicks too fast.
        if (!form.name) form.name = member?.name ?? "";
        if (!form.email) form.email = member?.email ?? "";

        // Prepare payload - exclude role if user cannot manage roles
        const canManageRoles = $page.props.auth?.can?.manageRoles;
        const payload = { ...form };
        if (!canManageRoles) {
            delete payload.role;
        }

        router.patch(`/admin/members/${member.id}`, payload, {
            preserveScroll: true,
            preserveState: true,
        });
    }

    function generateInvite() {
        router.post(
            `/admin/members/${member.id}/invite`,
            {},
            { preserveScroll: true },
        );
    }
</script>

<AdminLayout title="Scheda socio">
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <p class="text-sm text-muted-foreground">Anno tessera: {year}</p>
            <div class="flex items-center gap-2">
                <Button
                    variant="outline"
                    onclick={() => router.get("/admin/members")}
                >
                    Torna all’elenco
                </Button>
                <Button
                    onclick={save}
                    disabled={!hydrated || Object.keys(errorsLocal).length > 0}
                >
                    Salva
                </Button>
            </div>
        </div>

        {#if flash?.success}
            <div class="text-sm text-green-600 dark:text-green-400">
                {flash.success}
            </div>
        {/if}
        {#if flash?.error}
            <div class="text-sm text-destructive">{flash.error}</div>
        {/if}

        <div class="grid gap-6 lg:grid-cols-3 items-start">
            <div class="lg:col-span-1 space-y-6">
                <Card.Root>
                    <Card.Header>
                        <Card.Title>UUID + QR</Card.Title>
                        <Card.Description>
                            QRCode dell’UUID del socio (per lettori/PWA).
                        </Card.Description>
                    </Card.Header>
                    <Card.Content class="space-y-4">
                        <div class="text-xs font-mono break-all">{uuid}</div>

                        <div class="flex justify-center">
                            {#if qrDataUrl}
                                <img
                                    src={qrDataUrl}
                                    alt="QR UUID"
                                    class="rounded bg-white p-2"
                                />
                            {:else}
                                <div class="text-sm text-muted-foreground">
                                    Generazione QR...
                                </div>
                            {/if}
                        </div>
                    </Card.Content>
                </Card.Root>

                {#if $page.props.auth?.can?.viewAdminRoles}
                    <Card.Root>
                        <Card.Header>
                            <Card.Title>Ruolo Applicativo</Card.Title>
                            <Card.Description>
                                Gestisci i permessi di accesso all'applicazione.
                            </Card.Description>
                        </Card.Header>
                        <Card.Content class="space-y-4">
                            <div class="space-y-1.5">
                                <div class="text-xs text-muted-foreground mb-1">
                                    Ruolo
                                </div>
                                <select
                                    bind:value={form.role}
                                    disabled={!$page.props.auth?.can
                                        ?.manageRoles}
                                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <option value="member"
                                        >Socio (Nessun accesso admin)</option
                                    >
                                    <option value="admin"
                                        >Admin (Gestione soci)</option
                                    >
                                    <option value="super_admin"
                                        >Super Admin (Accesso completo)</option
                                    >
                                </select>
                                {#if !$page.props.auth?.can?.manageRoles}
                                    <p
                                        class="text-xs text-amber-600 dark:text-amber-500 flex items-center gap-1.5 mt-2"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="14"
                                            height="14"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        >
                                            <path
                                                d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"
                                            />
                                            <path d="M12 9v4" />
                                            <path d="M12 17h.01" />
                                        </svg>
                                        Solo il Super Admin può modificare i ruoli
                                        applicativi
                                    </p>
                                {:else}
                                    <p class="text-xs text-muted-foreground">
                                        Attenzione: dare ruolo Admin o Super
                                        Admin permette l'accesso all'area
                                        riservata.
                                    </p>
                                {/if}
                            </div>
                        </Card.Content>
                    </Card.Root>
                {/if}
            </div>

            <div class="space-y-6 lg:col-span-2">
                <Card.Root>
                    <Card.Header>
                        <Card.Title>Accesso socio</Card.Title>
                        <Card.Description>
                            Genera un link invito (valido 24 ore, utilizzabile
                            una sola volta) per far accedere il socio e
                            completare onboarding.
                        </Card.Description>
                    </Card.Header>
                    <Card.Content class="space-y-3">
                        {#if !member.must_set_password}
                            <div
                                class="rounded-md bg-green-50 dark:bg-green-900/20 p-3 text-sm flex items-center gap-2 text-green-700 dark:text-green-400"
                            >
                                <div
                                    class="size-2 rounded-full bg-green-600 dark:bg-green-400"
                                ></div>
                                <span class="font-medium">Account attivato</span
                                >
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Il socio ha completato la registrazione e
                                impostato la password.
                            </p>
                        {:else if inviteUrl}
                            <div class="text-xs text-muted-foreground">
                                Link invito (copia ora, non visibile dopo):
                            </div>
                            <div class="flex gap-2 items-center">
                                <Input value={inviteUrl} readonly />
                                <Button
                                    variant="secondary"
                                    onclick={() =>
                                        navigator.clipboard?.writeText(
                                            inviteUrl,
                                        )}
                                >
                                    Copia
                                </Button>
                            </div>
                            <div class="pt-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onclick={() => router.visit(location.href)}
                                >
                                    Chiudi / Aggiorna
                                </Button>
                            </div>
                        {:else if member?.invitations?.[0]}
                            {@const activeInvite = member.invitations[0]}
                            <div
                                class="rounded-md bg-muted p-3 text-sm space-y-1"
                            >
                                <div
                                    class="font-medium flex items-center justify-between"
                                >
                                    <span>Invito attivo</span>
                                    <span
                                        class="text-xs font-normal bg-primary/10 text-primary px-1.5 py-0.5 rounded"
                                        >Valido</span
                                    >
                                </div>
                                <div class="text-muted-foreground text-xs">
                                    Scade il: {new Date(
                                        activeInvite.expires_at,
                                    ).toLocaleString()}
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <Button
                                    variant="destructive"
                                    class="w-full"
                                    onclick={() => {
                                        if (
                                            confirm(
                                                "Annullare l'invito esistente? Il link non funzionerà più.",
                                            )
                                        ) {
                                            router.delete(
                                                `/admin/members/${member.id}/invite`,
                                                { preserveScroll: true },
                                            );
                                        }
                                    }}
                                >
                                    Annulla invito
                                </Button>
                            </div>
                        {:else}
                            <Button variant="outline" onclick={generateInvite}>
                                Genera link invito (24h)
                            </Button>
                        {/if}
                    </Card.Content>
                </Card.Root>

                <Card.Root>
                    <Card.Header>
                        <Card.Title>Dati personali</Card.Title>
                    </Card.Header>
                    <Card.Content class="space-y-4">
                        <div class="space-y-1.5">
                            <div class="text-xs text-muted-foreground">
                                Ruolo Pro Loco (incarico)
                            </div>
                            <select
                                bind:value={form.plv_role}
                                class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                            >
                                <option value="">— Seleziona —</option>
                                <option value="PRESIDENTE">PRESIDENTE</option>
                                <option value="VICE PRESIDENTE"
                                    >VICE PRESIDENTE</option
                                >
                                <option value="CASSIERE">CASSIERE</option>
                                <option value="SEGRETARIO">SEGRETARIO</option>
                                <option value="MAGAZZINIERE"
                                    >MAGAZZINIERE</option
                                >
                                <option value="CONSIGLIERE">CONSIGLIERE</option>
                                <option value="PRESIDENTE DEI REVISORI"
                                    >PRESIDENTE DEI REVISORI</option
                                >
                                <option value="REVISORE">REVISORE</option>
                                <option value="SUPPLENTE REVISORE"
                                    >SUPPLENTE REVISORE</option
                                >
                                <option value="PRESIDENTE DEI PROBIVIRI"
                                    >PRESIDENTE DEI PROBIVIRI</option
                                >
                                <option value="PROBIVIRO">PROBIVIRO</option>
                            </select>
                            {#if errorsLocal?.plv_role}
                                <p class="text-sm text-destructive">
                                    {errorsLocal.plv_role}
                                </p>
                            {/if}
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <div class="text-xs text-muted-foreground mb-1">
                                    Nome
                                </div>
                                <Input
                                    bind:value={form.first_name}
                                    placeholder="Nome"
                                />
                            </div>
                            <div>
                                <div class="text-xs text-muted-foreground mb-1">
                                    Cognome
                                </div>
                                <Input
                                    bind:value={form.last_name}
                                    placeholder="Cognome"
                                />
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <div class="text-xs text-muted-foreground mb-1">
                                    Data di nascita
                                </div>
                                <Input
                                    type="date"
                                    bind:value={form.birth_date}
                                />
                            </div>
                            <div>
                                <div class="text-xs text-muted-foreground mb-1">
                                    Telefono
                                </div>
                                <Input
                                    bind:value={form.phone}
                                    placeholder="Numero di telefono"
                                />
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <div class="text-xs text-muted-foreground mb-1">
                                    Email
                                </div>
                                <Input
                                    type="email"
                                    bind:value={form.email}
                                    placeholder="Email"
                                />
                            </div>
                            <div>
                                <div class="text-xs text-muted-foreground mb-1">
                                    Nome visualizzato
                                </div>
                                <Input
                                    bind:value={form.name}
                                    placeholder="Nome (visualizzato)"
                                />
                            </div>
                        </div>

                        <div class="border-t border-border pt-4 space-y-3">
                            <div class="text-sm font-medium">
                                Luogo di nascita
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <div
                                        class="text-xs text-muted-foreground mb-1"
                                    >
                                        Italia / Estero
                                    </div>
                                    <select
                                        bind:value={form.birth_place_type}
                                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                    >
                                        <option value="it">Italia</option>
                                        <option value="foreign">Estero</option>
                                    </select>
                                </div>
                            </div>

                            {#if form.birth_place_type === "it"}
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <div
                                            class="text-xs text-muted-foreground mb-1"
                                        >
                                            Provincia
                                        </div>
                                        <select
                                            bind:value={
                                                form.birth_province_code
                                            }
                                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                        >
                                            <option value=""
                                                >Seleziona provincia...</option
                                            >
                                            {#each italyPlaces.provinces as p (p.code)}
                                                <option value={p.code}
                                                    >{p.name} ({p.code})</option
                                                >
                                            {/each}
                                        </select>
                                    </div>
                                    <div>
                                        <div
                                            class="text-xs text-muted-foreground mb-1"
                                        >
                                            Città
                                        </div>
                                        <select
                                            bind:value={form.birth_city}
                                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                            disabled={!form.birth_province_code}
                                        >
                                            <option value=""
                                                >Seleziona città...</option
                                            >
                                            {#each citiesForBirthProvince as c (c)}
                                                <option value={c}>{c}</option>
                                            {/each}
                                        </select>
                                    </div>
                                </div>
                            {:else}
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <div
                                            class="text-xs text-muted-foreground mb-1"
                                        >
                                            Città
                                        </div>
                                        <Input
                                            bind:value={form.birth_city}
                                            placeholder="Città (estero)"
                                        />
                                    </div>
                                    <div>
                                        <div
                                            class="text-xs text-muted-foreground mb-1"
                                        >
                                            Nazione
                                        </div>
                                        <Input
                                            bind:value={form.birth_country}
                                            placeholder="Nazione"
                                        />
                                    </div>
                                </div>
                            {/if}
                        </div>
                    </Card.Content>
                </Card.Root>

                <Card.Root>
                    <Card.Header>
                        <Card.Title>Residenza</Card.Title>
                    </Card.Header>
                    <Card.Content class="space-y-4">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <div class="text-xs text-muted-foreground mb-1">
                                    Italia / Estero
                                </div>
                                <select
                                    bind:value={form.residence_type}
                                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                >
                                    <option value="it">Italia</option>
                                    <option value="foreign">Estero</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3">
                            <div class="sm:col-span-2">
                                <div class="text-xs text-muted-foreground mb-1">
                                    Via
                                </div>
                                <Input
                                    bind:value={form.residence_street}
                                    placeholder="Via"
                                />
                            </div>
                            <div>
                                <div class="text-xs text-muted-foreground mb-1">
                                    Numero civico
                                </div>
                                <Input
                                    bind:value={form.residence_house_number}
                                    placeholder="N."
                                />
                            </div>
                        </div>

                        <div>
                            <div class="text-xs text-muted-foreground mb-1">
                                Frazione
                            </div>
                            <Input
                                bind:value={form.residence_locality}
                                placeholder="Frazione"
                            />
                        </div>

                        {#if form.residence_type === "it"}
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <div
                                        class="text-xs text-muted-foreground mb-1"
                                    >
                                        Provincia
                                    </div>
                                    <select
                                        bind:value={
                                            form.residence_province_code
                                        }
                                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                    >
                                        <option value=""
                                            >Seleziona provincia...</option
                                        >
                                        {#each italyPlaces.provinces as p (p.code)}
                                            <option value={p.code}
                                                >{p.name} ({p.code})</option
                                            >
                                        {/each}
                                    </select>
                                </div>
                                <div>
                                    <div
                                        class="text-xs text-muted-foreground mb-1"
                                    >
                                        Città
                                    </div>
                                    <select
                                        bind:value={form.residence_city}
                                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                        disabled={!form.residence_province_code}
                                    >
                                        <option value=""
                                            >Seleziona città...</option
                                        >
                                        {#each citiesForResidenceProvince as c (c)}
                                            <option value={c}>{c}</option>
                                        {/each}
                                    </select>
                                </div>
                            </div>
                        {:else}
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <div
                                        class="text-xs text-muted-foreground mb-1"
                                    >
                                        Città
                                    </div>
                                    <Input
                                        bind:value={form.residence_city}
                                        placeholder="Città (estero)"
                                    />
                                </div>
                                <div>
                                    <div
                                        class="text-xs text-muted-foreground mb-1"
                                    >
                                        Nazione
                                    </div>
                                    <Input
                                        bind:value={form.residence_country}
                                        placeholder="Nazione"
                                    />
                                </div>
                            </div>
                        {/if}
                    </Card.Content>
                </Card.Root>

                <Card.Root>
                    <Card.Header>
                        <Card.Title>Iscrizione PLV</Card.Title>
                    </Card.Header>
                    <Card.Content class="space-y-4">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <div class="text-xs text-muted-foreground mb-1">
                                    Data iscrizione PLV
                                </div>
                                <Input
                                    type="date"
                                    bind:value={form.plv_joined_at}
                                />
                            </div>

                            <div
                                class="flex items-center justify-between space-x-2 pt-6"
                            >
                                <Label
                                    for="membership-toggle"
                                    class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                >
                                    Tesseramento {year}
                                </Label>
                                <Switch
                                    id="membership-toggle"
                                    checked={member.memberships &&
                                        member.memberships.some(
                                            (m) => m.year === year,
                                        )}
                                    onCheckedChange={(checked) => {
                                        if (checked) {
                                            router.post(
                                                `/admin/members/${member.id}/membership`,
                                                { year },
                                                { preserveScroll: true },
                                            );
                                        } else {
                                            router.delete(
                                                `/admin/members/${member.id}/membership`,
                                                {
                                                    data: { year },
                                                    preserveScroll: true,
                                                },
                                            );
                                        }
                                    }}
                                />
                            </div>

                            <div>
                                <div class="text-xs text-muted-foreground mb-1">
                                    Scadenza iscrizione (1 anno)
                                </div>
                                <Input value={expiresPreview} disabled />
                            </div>
                        </div>

                        {@const isActive = (() => {
                            if (
                                member.memberships &&
                                member.memberships.length > 0
                            )
                                return true;
                            if (!expiresPreview) return false;
                            const d = new Date(expiresPreview);
                            if (Number.isNaN(d.getTime())) return false;
                            const today = new Date();
                            today.setHours(0, 0, 0, 0);
                            return d >= today;
                        })()}

                        <div class="flex items-center justify-between pt-2">
                            <span class="text-sm font-medium"
                                >Stato Iscrizione:</span
                            >
                            {#if isActive}
                                <div
                                    class="rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 px-3 py-1 text-sm font-medium"
                                >
                                    Attivo
                                </div>
                            {:else}
                                <div
                                    class="rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 px-3 py-1 text-sm font-medium"
                                >
                                    Scaduto / Non Attivo
                                </div>
                            {/if}
                        </div>

                        <div class="text-xs text-muted-foreground">
                            La scadenza viene calcolata automaticamente dal
                            backend come 1 anno dalla data iscrizione.
                        </div>
                    </Card.Content>
                </Card.Root>
            </div>
        </div>
    </div>
</AdminLayout>
