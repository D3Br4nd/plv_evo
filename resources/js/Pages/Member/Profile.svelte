<script>
    /* eslint-disable */
    import { page, router } from "@inertiajs/svelte";
    import MemberLayout from "@/layouts/MemberLayout.svelte";
    import * as Card from "@/lib/components/ui/card";
    import { Button } from "@/lib/components/ui/button";
    import { Input } from "@/lib/components/ui/input";
    import * as Avatar from "@/lib/components/ui/avatar/index.js";
    import { toast } from "svelte-sonner";

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
    });

    $effect(() => {
        if (!user) return;
        form.name = user?.name || "";
        form.first_name = user?.first_name || "";
        form.last_name = user?.last_name || "";
        form.email = user?.email || "";
        form.phone = user?.phone || "";
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
</script>

<MemberLayout title="Profilo">
    <div class="space-y-4">
        <Card.Root>
            <Card.Content class="p-4">
                <div class="flex items-center gap-3">
                    <Avatar.Root class="size-12">
                        <Avatar.Image src={user?.avatar_url} alt={user?.name} />
                        <Avatar.Fallback class="bg-primary text-primary-foreground font-semibold">
                            {initials(user?.name)}
                        </Avatar.Fallback>
                    </Avatar.Root>
                    <div class="min-w-0">
                        <div class="font-semibold truncate">{user?.name}</div>
                        <div class="text-xs text-muted-foreground truncate">{user?.email}</div>
                    </div>
                </div>
            </Card.Content>
        </Card.Root>

        <Card.Root>
            <Card.Header>
                <Card.Title>I miei dati</Card.Title>
                <Card.Description>Modifica i tuoi dati personali.</Card.Description>
            </Card.Header>
            <Card.Content class="p-4 space-y-4">
                <div class="space-y-1.5">
                    <div class="text-xs text-muted-foreground">Avatar</div>
                    <div class="flex items-center gap-2">
                        <Input
                            type="file"
                            accept="image/*"
                            disabled={processing}
                            onchange={(e) => (avatarFile = e.currentTarget.files?.[0] || null)}
                        />
                        <Button variant="outline" disabled={processing || !avatarFile} onclick={uploadAvatar}>
                            Carica
                        </Button>
                        <Button variant="destructive" disabled={processing || !user?.avatar_url} onclick={removeAvatar}>
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

                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <div class="text-xs text-muted-foreground">Nome</div>
                        <Input bind:value={form.first_name} placeholder="Nome" disabled={processing} />
                    </div>
                    <div class="space-y-1.5">
                        <div class="text-xs text-muted-foreground">Cognome</div>
                        <Input bind:value={form.last_name} placeholder="Cognome" disabled={processing} />
                    </div>
                </div>

                <div class="space-y-1.5">
                    <div class="text-xs text-muted-foreground">Nome visualizzato</div>
                    <Input bind:value={form.name} placeholder="Nome visualizzato" disabled={processing} />
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <div class="text-xs text-muted-foreground">Email</div>
                        <Input type="email" bind:value={form.email} placeholder="Email" disabled={processing} />
                    </div>
                    <div class="space-y-1.5">
                        <div class="text-xs text-muted-foreground">Telefono</div>
                        <Input bind:value={form.phone} placeholder="Telefono" disabled={processing} />
                    </div>
                </div>

                <div class="flex justify-end">
                    <Button onclick={save} disabled={processing}>
                        {processing ? "Salvataggio..." : "Salva"}
                    </Button>
                </div>
            </Card.Content>
        </Card.Root>

        <Card.Root>
            <Card.Content class="p-4 space-y-2">
                {#if isAdmin}
                    <Button class="w-full" variant="secondary" onclick={() => router.get("/ui/admin")}>
                        Apri amministrazione
                    </Button>
                {/if}
                <Button class="w-full" variant="outline" onclick={() => router.get("/me/card")}>
                    Tessera (QR token)
                </Button>
                <Button class="w-full" variant="outline" onclick={() => router.get("/me/onboarding")}>
                    Onboarding / Notifiche / Password
                </Button>
                <Button class="w-full" variant="destructive" onclick={() => router.post("/logout")}>
                    Esci
                </Button>
            </Card.Content>
        </Card.Root>
    </div>
</MemberLayout>


