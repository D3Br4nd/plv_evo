<script>
    /* eslint-disable */
    import { page, router } from "@inertiajs/svelte";
    import MemberLayout from "@/layouts/MemberLayout.svelte";
    import * as Card from "@/lib/components/ui/card";
    import { Button } from "@/lib/components/ui/button";
    import { Input } from "@/lib/components/ui/input";
    import * as Avatar from "@/lib/components/ui/avatar/index.js";
    import { toast } from "svelte-sonner";
    import { Bell, BellOff, Info, Check } from "lucide-svelte";
    import * as Select from "@/lib/components/ui/select";
    import { Label } from "@/lib/components/ui/label";
    import italyPlaces from "@/data/italy_places.json";
    import { untrack } from "svelte";

    let { vapidPublicKey, hasPushSubscription } = $props();
    let user = $derived($page.props.auth?.user);
    let isAdmin = $derived(["super_admin", "admin"].includes(user?.role));

    function initials(name) {
        if (!name) return "U";
        return name
            .split(" ")
            .filter(Boolean)
            .map((n) => n[0])
            .join("")
            .toUpperCase()
            .slice(0, 2);
    }

    let processing = $state(false);
    let form = $state({
        name: "",
        first_name: "",
        last_name: "",
        email: "",
        phone: "",
        residence_type: "it",
        residence_street: "",
        residence_house_number: "",
        residence_city: "",
        residence_province_code: "",
        residence_country: "",
    });

    let citiesForResidenceProvince = $derived(
        italyPlaces.citiesByProvince?.[form.residence_province_code] ?? [],
    );

    $effect(() => {
        if (!user) return;
        form.name = user?.name || "";
        form.first_name = user?.first_name || "";
        form.last_name = user?.last_name || "";
        form.email = user?.email || "";
        form.phone = user?.phone || "";
        form.residence_type = user?.residence_type || "it";
        form.residence_street = user?.residence_street || "";
        form.residence_house_number = user?.residence_house_number || "";
        form.residence_city = user?.residence_city || "";
        form.residence_province_code = user?.residence_province_code || "";
        form.residence_country = user?.residence_country || "";
    });

    function save() {
        processing = true;
        router.patch("/me/profile", form, {
            preserveScroll: true,
            onFinish: () => {
                processing = false;
            },
            onSuccess: () => {
                toast.success("Profilo aggiornato.");
            },
            onError: () => {
                toast.error("Controlla i campi e riprova.");
            },
        });
    }

    let avatarFile = $state(null);
    let avatarPreviewUrl = $state(null);
    let lastAvatarObjectUrl = null;

    $effect(() => {
        if (lastAvatarObjectUrl) {
            URL.revokeObjectURL(lastAvatarObjectUrl);
            lastAvatarObjectUrl = null;
        }
        if (!avatarFile) {
            avatarPreviewUrl = null;
            return;
        }
        lastAvatarObjectUrl = URL.createObjectURL(avatarFile);
        avatarPreviewUrl = lastAvatarObjectUrl;
        return () => {
            if (lastAvatarObjectUrl) {
                URL.revokeObjectURL(lastAvatarObjectUrl);
                lastAvatarObjectUrl = null;
            }
        };
    });

    async function uploadAvatar() {
        if (!avatarFile) return;
        processing = true;
        const fd = new FormData();
        fd.append("avatar", avatarFile);
        router.post("/me/profile/avatar", fd, {
            preserveScroll: true,
            onFinish: () => {
                processing = false;
                avatarFile = null;
            },
            onSuccess: () => toast.success("Avatar aggiornato."),
            onError: () => toast.error("Impossibile caricare l’avatar."),
        });
    }

    async function removeAvatar() {
        if (!user?.avatar_url) return;
        processing = true;
        router.delete("/me/profile/avatar", {
            preserveScroll: true,
            onFinish: () => {
                processing = false;
                avatarFile = null;
            },
            onSuccess: () => toast.success("Avatar rimosso."),
            onError: () => toast.error("Impossibile rimuovere l’avatar."),
        });
    }

    // ---- Push notifications ----
    function urlBase64ToUint8Array(base64String) {
        const padding = "=".repeat((4 - (base64String.length % 4)) % 4);
        const base64 = (base64String + padding)
            .replace(/-/g, "+")
            .replace(/_/g, "/");
        const rawData = atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i)
            outputArray[i] = rawData.charCodeAt(i);
        return outputArray;
    }

    let pushEnabled = $state(false); // Default to false, check on mount
    let pushProcessing = $state(false);

    $effect(() => {
        if (typeof window !== "undefined" && "serviceWorker" in navigator) {
            navigator.serviceWorker.ready.then(async (reg) => {
                const sub = await reg.pushManager.getSubscription();
                if (import.meta.env.DEV)
                    console.log("Subscription found:", sub);
                pushEnabled = !!sub;
            });
        }
    });

    async function enableNotifications() {
        if (pushProcessing) return;
        if (!("Notification" in window) || !("serviceWorker" in navigator)) {
            toast.error("Notifiche non supportate.");
            return;
        }
        if (!vapidPublicKey) {
            toast.error("VAPID Key mancante.");
            return;
        }

        // Optimistic Update
        const previousState = pushEnabled;
        pushEnabled = true;
        pushProcessing = true;

        try {
            const permission = await Notification.requestPermission();
            if (permission !== "granted") {
                throw new Error("Permesso negato");
            }

            const reg = await navigator.serviceWorker.ready;
            const sub = await reg.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
            });

            const res = await fetch("/me/push-subscriptions", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute("content"),
                },
                body: JSON.stringify(sub),
            });

            if (!res.ok) throw new Error("Server error");

            toast.success("Notifiche abilitate!");
        } catch (e) {
            console.error(e);
            pushEnabled = previousState; // Revert on failure
            toast.error("Impossibile abilitare le notifiche.");
        } finally {
            pushProcessing = false;
        }
    }

    async function disableNotifications() {
        if (pushProcessing) return;

        // Optimistic Update
        const previousState = pushEnabled;
        pushEnabled = false;
        pushProcessing = true;

        try {
            if ("serviceWorker" in navigator) {
                const reg = await navigator.serviceWorker.ready;
                const sub = await reg.pushManager.getSubscription();
                if (sub) {
                    await sub.unsubscribe();

                    // Send endpoint to server to delete only this one
                    await fetch("/me/push-subscriptions", {
                        method: "DELETE",
                        headers: {
                            "Content-Type": "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                            "X-CSRF-TOKEN": document
                                .querySelector('meta[name="csrf-token"]')
                                ?.getAttribute("content"),
                        },
                        body: JSON.stringify({ endpoint: sub.endpoint }),
                    });
                }
            }

            if (!res.ok) throw new Error("Server error");

            toast.success("Notifiche disabilitate.");
        } catch (e) {
            console.error(e);
            pushEnabled = previousState; // Revert on failure
            toast.error("Errore durante la disabilitazione.");
        } finally {
            pushProcessing = false;
        }
    }
</script>

<MemberLayout title="Profilo">
    <div class="space-y-4">
        <!-- Membership Card with Expiry -->
        <Card.Root class="overflow-hidden border-primary/20 bg-primary/5">
            <Card.Header class="pb-2">
                <Card.Title class="text-primary flex items-center gap-2">
                    <Info class="size-4" />
                    Stato Tessera
                </Card.Title>
            </Card.Header>
            <Card.Content class="p-4 pt-0">
                <div class="flex flex-col gap-1">
                    <span class="text-sm font-medium">Scadenza:</span>
                    {#if user?.plv_expires_at}
                        <span
                            class="text-2xl font-bold font-mono tracking-tight"
                            >{user.plv_expires_at}</span
                        >
                    {:else}
                        <span class="text-lg text-muted-foreground"
                            >Nessuna scadenza.</span
                        >
                    {/if}
                </div>
            </Card.Content>
        </Card.Root>

        <Card.Root>
            <Card.Content class="p-4">
                <div class="flex items-center gap-3">
                    <Avatar.Root class="size-12">
                        <Avatar.Image src={user?.avatar_url} alt={user?.name} />
                        <Avatar.Fallback
                            class="bg-primary text-primary-foreground font-semibold"
                        >
                            {initials(user?.name)}
                        </Avatar.Fallback>
                    </Avatar.Root>
                    <div class="min-w-0">
                        <div class="font-semibold truncate">{user?.name}</div>
                        <div class="text-xs text-muted-foreground truncate">
                            {user?.email}
                        </div>
                    </div>
                </div>
            </Card.Content>
        </Card.Root>

        <Card.Root>
            <Card.Header>
                <Card.Title>I miei dati</Card.Title>
                <Card.Description
                    >Modifica i tuoi dati personali e di residenza.</Card.Description
                >
            </Card.Header>
            <Card.Content class="p-4 space-y-6">
                <!-- Avatar Section -->
                <div class="space-y-1.5">
                    <Label>Avatar</Label>
                    <div class="flex items-center gap-2">
                        <Input
                            type="file"
                            accept="image/*"
                            disabled={processing}
                            onchange={(e) =>
                                (avatarFile =
                                    e.currentTarget.files?.[0] || null)}
                        />
                        <Button
                            variant="outline"
                            disabled={processing || !avatarFile}
                            onclick={uploadAvatar}
                        >
                            Carica
                        </Button>
                        <Button
                            variant="destructive"
                            disabled={processing || !user?.avatar_url}
                            onclick={removeAvatar}
                        >
                            Rimuovi
                        </Button>
                    </div>
                    {#if avatarPreviewUrl || user?.avatar_url}
                        <div class="mt-2 rounded-lg border bg-card p-3">
                            <div class="flex items-center gap-3">
                                <img
                                    src={avatarPreviewUrl || user?.avatar_url}
                                    alt="Anteprima avatar"
                                    class="h-20 w-20 rounded-md object-cover border bg-background"
                                />
                                <div class="text-xs text-muted-foreground">
                                    {#if avatarPreviewUrl}
                                        Anteprima prima del caricamento.
                                    {:else}
                                        Avatar attuale.
                                    {/if}
                                </div>
                            </div>
                        </div>
                    {/if}
                </div>

                <!-- Personal Info -->
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <Label>Nome</Label>
                        <Input
                            bind:value={form.first_name}
                            placeholder="Nome"
                            disabled={processing}
                        />
                    </div>
                    <div class="space-y-1.5">
                        <Label>Cognome</Label>
                        <Input
                            bind:value={form.last_name}
                            placeholder="Cognome"
                            disabled={processing}
                        />
                    </div>
                </div>

                <div class="space-y-1.5">
                    <Label>Nome visualizzato</Label>
                    <Input
                        bind:value={form.name}
                        placeholder="Nome visualizzato"
                        disabled={processing}
                    />
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <Label>Email</Label>
                        <Input
                            type="email"
                            bind:value={form.email}
                            placeholder="Email"
                            disabled={processing}
                        />
                    </div>
                    <div class="space-y-1.5">
                        <Label>Telefono</Label>
                        <Input
                            bind:value={form.phone}
                            placeholder="Telefono"
                            disabled={processing}
                        />
                    </div>
                </div>

                <!-- Residence Info -->
                <div class="pt-4 border-t space-y-4">
                    <h3 class="text-sm font-semibold">Residenza</h3>

                    <div class="space-y-1.5">
                        <Label>Nazione</Label>
                        <Select.Root
                            type="single"
                            bind:value={form.residence_type}
                        >
                            <Select.Trigger>
                                {form.residence_type === "it"
                                    ? "Italia"
                                    : "Estero"}
                            </Select.Trigger>
                            <Select.Content>
                                <Select.Item value="it">Italia</Select.Item>
                                <Select.Item value="foreign">Estero</Select.Item
                                >
                            </Select.Content>
                        </Select.Root>
                    </div>

                    {#if form.residence_type === "it"}
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <Label>Via/Piazza</Label>
                                <Input
                                    bind:value={form.residence_street}
                                    placeholder="Via Roma"
                                    disabled={processing}
                                />
                            </div>
                            <div class="space-y-1.5">
                                <Label>Civico</Label>
                                <Input
                                    bind:value={form.residence_house_number}
                                    placeholder="10"
                                    disabled={processing}
                                />
                            </div>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <Label>Provincia</Label>
                                <select
                                    bind:value={form.residence_province_code}
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    disabled={processing}
                                >
                                    <option value="">Seleziona...</option>
                                    {#each italyPlaces.provinces as p (p.code)}
                                        <option value={p.code}
                                            >{p.name} ({p.code})</option
                                        >
                                    {/each}
                                </select>
                            </div>
                            <div class="space-y-1.5">
                                <Label>Città</Label>
                                <select
                                    bind:value={form.residence_city}
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    disabled={!form.residence_province_code ||
                                        processing}
                                >
                                    <option value="">Seleziona...</option>
                                    {#each citiesForResidenceProvince as c (c)}
                                        <option value={c}>{c}</option>
                                    {/each}
                                </select>
                            </div>
                        </div>
                    {:else}
                        <div class="space-y-1.5">
                            <Label>Indirizzo completo</Label>
                            <Input
                                bind:value={form.residence_street}
                                placeholder="Indirizzo estero"
                                disabled={processing}
                            />
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <Label>Città</Label>
                                <Input
                                    bind:value={form.residence_city}
                                    placeholder="Città"
                                    disabled={processing}
                                />
                            </div>
                            <div class="space-y-1.5">
                                <Label>Paese</Label>
                                <Input
                                    bind:value={form.residence_country}
                                    placeholder="Nazione"
                                    disabled={processing}
                                />
                            </div>
                        </div>
                    {/if}
                </div>

                <div class="flex justify-end">
                    <Button onclick={save} disabled={processing}>
                        {processing ? "Salvataggio..." : "Salva"}
                    </Button>
                </div>
            </Card.Content>
        </Card.Root>

        <!-- Notifiche Toggle (High Visibility) -->
        <Card.Root
            class={pushEnabled
                ? "border-green-500/20 bg-green-500/5"
                : "border-muted"}
        >
            <Card.Content class="p-4 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div
                        class={pushEnabled
                            ? "text-green-600 flex items-center gap-2"
                            : "text-muted-foreground"}
                    >
                        {#if pushEnabled}
                            <Check class="size-6" />
                            <Bell class="size-6" />
                        {:else}
                            <BellOff class="size-6" />
                        {/if}
                    </div>
                    <div>
                        <div class="font-semibold">Notifiche Push</div>
                        <div class="text-xs text-muted-foreground">
                            {pushEnabled
                                ? "Ricevi aggiornamenti."
                                : "Nessuna notifica."}
                        </div>
                    </div>
                </div>
                {#if pushEnabled}
                    <Button
                        variant="destructive"
                        size="sm"
                        onclick={disableNotifications}
                        disabled={pushProcessing}
                    >
                        Disabilita
                    </Button>
                {:else}
                    <Button
                        variant="outline"
                        size="sm"
                        onclick={enableNotifications}
                        disabled={pushProcessing}
                    >
                        Abilita
                    </Button>
                {/if}
            </Card.Content>
        </Card.Root>

        <Card.Root>
            <Card.Content class="p-4 space-y-2">
                {#if isAdmin}
                    <Button
                        class="w-full"
                        variant="secondary"
                        onclick={() => router.get("/ui/admin")}
                    >
                        Apri amministrazione
                    </Button>
                {/if}
                <Button
                    class="w-full"
                    variant="outline"
                    onclick={() => router.get("/me/card")}
                >
                    Tessera (QR token)
                </Button>
                <Button
                    class="w-full"
                    variant="outline"
                    onclick={() => router.get("/me/onboarding")}
                >
                    Onboarding / Password
                </Button>
                <Button
                    class="w-full"
                    variant="destructive"
                    onclick={() => router.post("/logout")}
                >
                    Esci
                </Button>
            </Card.Content>
        </Card.Root>
    </div>
</MemberLayout>
