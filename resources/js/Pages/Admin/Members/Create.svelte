<script>
    /* eslint-disable no-undef */
    import { page } from "@inertiajs/svelte";
    import { router } from "@inertiajs/svelte";

    import AdminLayout from "@/layouts/AdminLayout.svelte";
    import { Button } from "@/lib/components/ui/button";
    import { Input } from "@/lib/components/ui/input";
    import * as Card from "@/lib/components/ui/card";

    import italyPlaces from "@/data/italy_places.json";

    let { year } = $props();
    let flash = $derived($page.props.flash);
    let errors = $derived($page.props.errors || {});
    let canManageRoles = $derived($page.props.auth?.can?.manageRoles);

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

    let form = $state({
        // base account
        name: "",
        email: "",
        role: "member",
        plv_role: "",

        // profile
        first_name: "",
        last_name: "",
        phone: "",

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

    let expiresPreview = $derived(addOneYear(form.plv_joined_at));

    let citiesForBirthProvince = $derived(
        italyPlaces.citiesByProvince?.[form.birth_province_code] ?? [],
    );
    let citiesForResidenceProvince = $derived(
        italyPlaces.citiesByProvince?.[form.residence_province_code] ?? [],
    );

    let processing = $state(false);

    function save() {
        processing = true;
        router.post("/admin/members", form, {
            onFinish: () => {
                processing = false;
            },
        });
    }
</script>

<AdminLayout title="Nuovo socio">
    {#snippet headerActions()}
        <Button variant="outline" onclick={() => router.get("/admin/members")}>
            Torna all’elenco
        </Button>
        <Button onclick={save} disabled={processing}>
            {processing ? "Creazione..." : "Crea socio"}
        </Button>
    {/snippet}

    <div class="space-y-6">
        <p class="text-sm text-muted-foreground">
            Compila i dati del socio. Dopo la creazione verrà aperta la scheda completa.
        </p>

        {#if flash?.success}
            <div class="text-sm text-green-600 dark:text-green-400">{flash.success}</div>
        {/if}
        {#if flash?.error}
            <div class="text-sm text-destructive">{flash.error}</div>
        {/if}

        <div class="grid gap-6 lg:grid-cols-2">
            <Card.Root>
                <Card.Header>
                    <Card.Title>Account</Card.Title>
                    <Card.Description>Nome, email e ruolo.</Card.Description>
                </Card.Header>
                <Card.Content class="space-y-4">
                    <div class="space-y-1.5">
                        <div class="text-xs text-muted-foreground">Nome visualizzato</div>
                        <Input bind:value={form.name} placeholder="Nome (visualizzato)" />
                        {#if errors?.name}
                            <p class="text-sm text-destructive">{errors.name}</p>
                        {/if}
                    </div>
                    <div class="space-y-1.5">
                        <div class="text-xs text-muted-foreground">Email</div>
                        <Input type="email" bind:value={form.email} placeholder="Email" />
                        {#if errors?.email}
                            <p class="text-sm text-destructive">{errors.email}</p>
                        {/if}
                    </div>
                    <div class="space-y-1.5">
                        <div class="text-xs text-muted-foreground">Ruolo</div>
                        {#if canManageRoles}
                            <select
                                bind:value={form.role}
                                class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                            >
                                <option value="member">Socio</option>
                                <option value="admin">Admin</option>
                                <option value="super_admin">Super Admin</option>
                            </select>
                        {:else}
                            <Input value="Socio" disabled />
                        {/if}
                        {#if errors?.role}
                            <p class="text-sm text-destructive">{errors.role}</p>
                        {/if}
                    </div>
                    <div class="space-y-1.5">
                        <div class="text-xs text-muted-foreground">Ruolo Pro Loco (incarico)</div>
                        <select
                            bind:value={form.plv_role}
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option value="">— Seleziona —</option>
                            <option value="PRESIDENTE">PRESIDENTE</option>
                            <option value="VICE PRESIDENTE">VICE PRESIDENTE</option>
                            <option value="CASSIERE">CASSIERE</option>
                            <option value="SEGRETARIO">SEGRETARIO</option>
                            <option value="MAGAZZINIERE">MAGAZZINIERE</option>
                            <option value="CONSIGLIERE">CONSIGLIERE</option>
                            <option value="PRESIDENTE DEI REVISORI">PRESIDENTE DEI REVISORI</option>
                            <option value="REVISORE">REVISORE</option>
                            <option value="SUPPLENTE REVISORE">SUPPLENTE REVISORE</option>
                            <option value="PRESIDENTE DEI PROBIVIRI">PRESIDENTE DEI PROBIVIRI</option>
                            <option value="PROBIVIRO">PROBIVIRO</option>
                        </select>
                        {#if errors?.plv_role}
                            <p class="text-sm text-destructive">{errors.plv_role}</p>
                        {/if}
                        <p class="text-xs text-muted-foreground">
                            Questo campo descrive l’incarico nella Pro Loco. I permessi del backend restano separati.
                        </p>
                    </div>
                </Card.Content>
            </Card.Root>

            <Card.Root>
                <Card.Header>
                    <Card.Title>Dati personali</Card.Title>
                </Card.Header>
                <Card.Content class="space-y-4">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <div class="text-xs text-muted-foreground mb-1">Nome</div>
                            <Input bind:value={form.first_name} placeholder="Nome" />
                        </div>
                        <div>
                            <div class="text-xs text-muted-foreground mb-1">Cognome</div>
                            <Input bind:value={form.last_name} placeholder="Cognome" />
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <div class="text-xs text-muted-foreground mb-1">Data di nascita</div>
                            <Input type="date" bind:value={form.birth_date} />
                        </div>
                        <div>
                            <div class="text-xs text-muted-foreground mb-1">Telefono</div>
                            <Input bind:value={form.phone} placeholder="Numero di telefono" />
                        </div>
                    </div>

                    <div class="border-t border-border pt-4 space-y-3">
                        <div class="text-sm font-medium">Luogo di nascita</div>
                        <div>
                            <div class="text-xs text-muted-foreground mb-1">Italia / Estero</div>
                            <select
                                bind:value={form.birth_place_type}
                                class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                            >
                                <option value="it">Italia</option>
                                <option value="foreign">Estero</option>
                            </select>
                        </div>

                        {#if form.birth_place_type === "it"}
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <div class="text-xs text-muted-foreground mb-1">Provincia</div>
                                    <select
                                        bind:value={form.birth_province_code}
                                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                    >
                                        <option value="">Seleziona provincia...</option>
                                        {#each italyPlaces.provinces as p (p.code)}
                                            <option value={p.code}>{p.name} ({p.code})</option>
                                        {/each}
                                    </select>
                                </div>
                                <div>
                                    <div class="text-xs text-muted-foreground mb-1">Città</div>
                                    <select
                                        bind:value={form.birth_city}
                                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                        disabled={!form.birth_province_code}
                                    >
                                        <option value="">Seleziona città...</option>
                                        {#each citiesForBirthProvince as c (c)}
                                            <option value={c}>{c}</option>
                                        {/each}
                                    </select>
                                </div>
                            </div>
                        {:else}
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <div class="text-xs text-muted-foreground mb-1">Città</div>
                                    <Input bind:value={form.birth_city} placeholder="Città (estero)" />
                                </div>
                                <div>
                                    <div class="text-xs text-muted-foreground mb-1">Nazione</div>
                                    <Input bind:value={form.birth_country} placeholder="Nazione" />
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
                    <div>
                        <div class="text-xs text-muted-foreground mb-1">Italia / Estero</div>
                        <select
                            bind:value={form.residence_type}
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option value="it">Italia</option>
                            <option value="foreign">Estero</option>
                        </select>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="sm:col-span-2">
                            <div class="text-xs text-muted-foreground mb-1">Via</div>
                            <Input bind:value={form.residence_street} placeholder="Via" />
                        </div>
                        <div>
                            <div class="text-xs text-muted-foreground mb-1">Numero civico</div>
                            <Input bind:value={form.residence_house_number} placeholder="N." />
                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-muted-foreground mb-1">Frazione</div>
                        <Input bind:value={form.residence_locality} placeholder="Frazione" />
                    </div>

                    {#if form.residence_type === "it"}
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <div class="text-xs text-muted-foreground mb-1">Provincia</div>
                                <select
                                    bind:value={form.residence_province_code}
                                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                >
                                    <option value="">Seleziona provincia...</option>
                                    {#each italyPlaces.provinces as p (p.code)}
                                        <option value={p.code}>{p.name} ({p.code})</option>
                                    {/each}
                                </select>
                            </div>
                            <div>
                                <div class="text-xs text-muted-foreground mb-1">Città</div>
                                <select
                                    bind:value={form.residence_city}
                                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                    disabled={!form.residence_province_code}
                                >
                                    <option value="">Seleziona città...</option>
                                    {#each citiesForResidenceProvince as c (c)}
                                        <option value={c}>{c}</option>
                                    {/each}
                                </select>
                            </div>
                        </div>
                    {:else}
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <div class="text-xs text-muted-foreground mb-1">Città</div>
                                <Input bind:value={form.residence_city} placeholder="Città (estero)" />
                            </div>
                            <div>
                                <div class="text-xs text-muted-foreground mb-1">Nazione</div>
                                <Input bind:value={form.residence_country} placeholder="Nazione" />
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
                            <div class="text-xs text-muted-foreground mb-1">Data iscrizione PLV</div>
                            <Input type="date" bind:value={form.plv_joined_at} />
                        </div>
                        <div>
                            <div class="text-xs text-muted-foreground mb-1">Scadenza (1 anno)</div>
                            <Input value={expiresPreview} disabled />
                        </div>
                    </div>
                    <div class="text-xs text-muted-foreground">
                        La scadenza viene calcolata automaticamente dal backend come 1 anno dalla data iscrizione.
                    </div>
                </Card.Content>
            </Card.Root>
        </div>
    </div>
</AdminLayout>


