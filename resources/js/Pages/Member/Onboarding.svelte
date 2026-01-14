<script>
    /* eslint-disable */
    import { page } from "@inertiajs/svelte";
    import { router } from "@inertiajs/svelte";

    import { onMount } from "svelte";
    import MemberLayout from "@/layouts/MemberLayout.svelte";
    import { Button, buttonVariants } from "@/lib/components/ui/button";
    import { Input } from "@/lib/components/ui/input";
    import * as Card from "@/lib/components/ui/card";
    import * as Tooltip from "@/lib/components/ui/tooltip/index.js";
    import * as Dialog from "@/lib/components/ui/dialog";
    import { cn } from "@/lib/utils/cn";
    import { Download, Info } from "lucide-svelte";
    import { toast } from "svelte-sonner";

    let { user, vapidPublicKey, hasPushSubscription } = $props();
    let flash = $derived($page.props.flash);

    // ---- PWA install prompt ----
    let deferredPrompt = $state(null);
    let isInstalled = $state(false);
    let isIos = $state(false);

    function computeInstalled() {
        try {
            const standalone =
                window.matchMedia?.("(display-mode: standalone)")?.matches ||
                window.navigator?.standalone === true;
            isInstalled = !!standalone;
        } catch (e) {
            isInstalled = false;
        }
    }

    async function installPwa() {
        computeInstalled();
        if (isInstalled) {
            toast.message("App già installata.");
            return;
        }

        if (deferredPrompt) {
            deferredPrompt.prompt();
            const choice = await deferredPrompt.userChoice;
            deferredPrompt = null;
            if (choice?.outcome === "accepted") toast.success("Installazione avviata.");
            else toast.message("Installazione annullata.");
            return;
        }

        // iOS Safari (no prompt)
        if (isIos) {
            toast.message("Su iPhone/iPad: Condividi → Aggiungi a Home.");
            return;
        }

        toast.message("Apri il menu del browser e scegli “Installa app”.");
    }

    onMount(() => {
        const ua = window.navigator?.userAgent?.toLowerCase?.() || "";
        isIos = /iphone|ipad|ipod/.test(ua);

        computeInstalled();

        const onBip = (e) => {
            // Chrome/Edge on Android: capture install prompt
            e.preventDefault();
            deferredPrompt = e;
        };

        const onInstalled = () => {
            deferredPrompt = null;
            computeInstalled();
        };

        window.addEventListener("beforeinstallprompt", onBip);
        window.addEventListener("appinstalled", onInstalled);

        return () => {
            window.removeEventListener("beforeinstallprompt", onBip);
            window.removeEventListener("appinstalled", onInstalled);
        };
    });

    // ---- Password ----
    let pwd = $state({
        current_password: "",
        password: "",
        password_confirmation: "",
    });

    function savePassword() {
        const payload = { ...pwd };
        // On first setup, backend doesn't require current_password
        if (user.must_set_password) delete payload.current_password;
        router.patch("/me/password", payload, { preserveScroll: true });
    }

    // ---- Push notifications ----
    function urlBase64ToUint8Array(base64String) {
        const padding = "=".repeat((4 - (base64String.length % 4)) % 4);
        const base64 = (base64String + padding).replace(/-/g, "+").replace(/_/g, "/");
        const rawData = atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) outputArray[i] = rawData.charCodeAt(i);
        return outputArray;
    }

    let notificationStatus = $state("");
    let pushEnabled = $state(false);
    let pushProcessing = $state(false);

    $effect(() => {
        pushEnabled = !!hasPushSubscription;
    });

    async function enableNotifications() {
        if (pushProcessing) return;
        if (!("Notification" in window) || !("serviceWorker" in navigator)) {
            notificationStatus = "Notifiche non supportate su questo browser.";
            return;
        }
        if (!vapidPublicKey) {
            notificationStatus =
                "VAPID_PUBLIC_KEY non configurata sul server: impossibile registrare notifiche.";
            return;
        }

        pushProcessing = true;
        try {
            const permission = await Notification.requestPermission();
            if (permission !== "granted") {
                notificationStatus = "Permesso notifiche non concesso.";
                return;
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

            if (!res.ok) {
                pushEnabled = false;
                notificationStatus =
                    "Errore durante la registrazione sul server: riprova.";
                return;
            }

            pushEnabled = true;
            notificationStatus = "Notifiche abilitate.";
        } catch (e) {
            pushEnabled = false;
            notificationStatus = "Errore: impossibile abilitare le notifiche.";
        } finally {
            pushProcessing = false;
        }
    }

    async function disableNotifications() {
        if (pushProcessing) return;
        if (!("serviceWorker" in navigator)) {
            notificationStatus = "Notifiche non supportate su questo browser.";
            return;
        }

        pushProcessing = true;
        try {
            const reg = await navigator.serviceWorker.ready;
            const sub = await reg.pushManager.getSubscription();
            if (sub) await sub.unsubscribe();

            const res = await fetch("/me/push-subscriptions", {
                method: "DELETE",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute("content"),
                },
            });

            if (!res.ok) {
                notificationStatus =
                    "Errore durante la disattivazione sul server: riprova.";
                return;
            }

            pushEnabled = false;
            notificationStatus = "Notifiche disabilitate.";
        } catch (e) {
            notificationStatus = "Errore: impossibile disabilitare le notifiche.";
        } finally {
            pushProcessing = false;
        }
    }
</script>

<MemberLayout title="Onboarding">
    {#snippet headerActions()}
        <Button variant="outline" onclick={() => router.get("/me/card")}>
            Vai alla tessera
        </Button>
    {/snippet}

    <div class="max-w-xl space-y-6">
        <h2 class="text-2xl font-bold">Benvenuto</h2>

        {#if flash?.success}
            <div class="text-sm text-green-600 dark:text-green-400">{flash.success}</div>
        {/if}
        {#if flash?.error}
            <div class="text-sm text-destructive">{flash.error}</div>
        {/if}

        <Card.Root>
            <Card.Header>
                <Card.Title>1) Installa la PWA</Card.Title>
                <Card.Description>
                    Installa l’app sul telefono per accesso rapido e migliore esperienza.
                </Card.Description>
            </Card.Header>
            <Card.Content class="space-y-4">
                <div class="space-y-2 text-sm text-muted-foreground">
                    <p>
                        <span class="font-medium text-foreground">iPhone/iPad:</span>
                        Tocca l'icona <span class="font-medium text-foreground">Condividi</span> e poi
                        <span class="font-medium text-foreground">Aggiungi a Home</span>.
                    </p>
                    <p>
                        <span class="font-medium text-foreground">Android:</span>
                        Tocca il tasto qui sotto oppure <span class="font-medium text-foreground">Menu ⋮</span> →
                        <span class="font-medium text-foreground">Installa app</span>.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                        <Button variant="default" onclick={installPwa} disabled={isInstalled}>
                            <Download class="mr-2 size-4" />
                            {isInstalled ? "App installata" : "Installa app"}
                        </Button>

                    <!-- Desktop: tooltip (hover). Mobile: dialog (tap). -->
                    <div class="hidden sm:block">
                        <Tooltip.Provider>
                            <Tooltip.Root>
                                <Tooltip.Trigger>
                                    {#snippet child({ props })}
                                        <button
                                            {...props}
                                            type="button"
                                            class={cn(
                                                buttonVariants({ variant: "ghost", size: "icon" }),
                                                "h-10 w-10"
                                            )}
                                            aria-label="Istruzioni installazione"
                                        >
                                            <div class="flex items-center justify-center">
                                                <Info class="size-4" />
                                            </div>
                                        </button>
                                    {/snippet}
                                </Tooltip.Trigger>
                                <Tooltip.Content class="max-w-xs" side="bottom" sideOffset={8}>
                                    <div class="space-y-3 text-sm">
                                        <div class="font-medium">Installazione PWA</div>
                                        <div class="space-y-2">
                                            <div class="font-medium text-xs text-muted-foreground">Android (Chrome)</div>
                                            <div class="text-xs text-muted-foreground">
                                                Tocca <span class="font-medium text-foreground">Installa app</span> oppure:
                                                menu ⋮ → <span class="font-medium text-foreground">Installa app</span>.
                                            </div>
                                        </div>
                                        <div class="space-y-2">
                                            <div class="font-medium text-xs text-muted-foreground">iPhone/iPad (Safari)</div>
                                            <div class="text-xs text-muted-foreground">
                                                Tocca <span class="font-medium text-foreground">Condividi</span> →
                                                <span class="font-medium text-foreground">Aggiungi a Home</span>.
                                            </div>
                                        </div>
                                    </div>
                                </Tooltip.Content>
                            </Tooltip.Root>
                        </Tooltip.Provider>
                    </div>

                    <div class="sm:hidden">
                        <Dialog.Root>
                            <Dialog.Trigger>
                                {#snippet child({ props })}
                                    <button
                                        {...props}
                                        type="button"
                                        class={cn(
                                            buttonVariants({ variant: "ghost", size: "icon" }),
                                            "h-10 w-10"
                                        )}
                                        aria-label="Istruzioni installazione"
                                    >
                                        <div class="flex items-center justify-center">
                                            <Info class="size-4" />
                                        </div>
                                    </button>
                                {/snippet}
                            </Dialog.Trigger>
                            <Dialog.Content class="max-w-md">
                                <Dialog.Header>
                                    <Dialog.Title>Installazione PWA</Dialog.Title>
                                    <Dialog.Description>
                                        Procedura di installazione su Android e iOS.
                                    </Dialog.Description>
                                </Dialog.Header>

                                <div class="mt-4 space-y-4 text-sm">
                                    <div class="space-y-1">
                                        <div class="font-medium">Android (Chrome)</div>
                                        <div class="text-muted-foreground">
                                            Tocca <span class="font-medium text-foreground">Installa app</span> oppure:
                                            menu ⋮ → <span class="font-medium text-foreground">Installa app</span>.
                                        </div>
                                    </div>
                                    <div class="space-y-1">
                                        <div class="font-medium">iPhone/iPad (Safari)</div>
                                        <div class="text-muted-foreground">
                                            Tocca <span class="font-medium text-foreground">Condividi</span> →
                                            <span class="font-medium text-foreground">Aggiungi a Home</span>.
                                        </div>
                                    </div>
                                </div>

                                <Dialog.Footer class="mt-6">
                                    <Dialog.Close>
                                        {#snippet child({ props })}
                                            <Button {...props} variant="outline">Chiudi</Button>
                                        {/snippet}
                                    </Dialog.Close>
                                </Dialog.Footer>
                            </Dialog.Content>
                        </Dialog.Root>
                    </div>
                </div>
            </Card.Content>
        </Card.Root>

        <Card.Root>
            <Card.Header>
                <Card.Title>2) Abilita notifiche</Card.Title>
                <Card.Description>
                    Riceverai comunicazioni su eventi e aggiornamenti.
                </Card.Description>
            </Card.Header>
            <Card.Content class="space-y-3">
                {#if pushEnabled}
                    <div class="text-sm text-muted-foreground">
                        Notifiche: già abilitate su questo account.
                    </div>
                {/if}
                {#if pushEnabled}
                    <Button variant="outline" onclick={disableNotifications} disabled={pushProcessing}>
                        Disabilita notifiche
                    </Button>
                {:else}
                    <Button onclick={enableNotifications} disabled={pushProcessing}>
                        Abilita notifiche
                    </Button>
                {/if}
                {#if notificationStatus}
                    <div class="text-sm text-muted-foreground">{notificationStatus}</div>
                {/if}
            </Card.Content>
        </Card.Root>

        <Card.Root>
            <Card.Header>
                <Card.Title>3) Imposta/Cambia password</Card.Title>
                <Card.Description>
                    Ti consigliamo di impostare una password personale.
                </Card.Description>
            </Card.Header>
            <Card.Content class="space-y-3">
                {#if !user.must_set_password}
                    <div>
                        <div class="text-xs text-muted-foreground mb-1">Password attuale</div>
                        <Input type="password" bind:value={pwd.current_password} />
                    </div>
                {/if}
                <div>
                    <div class="text-xs text-muted-foreground mb-1">Nuova password</div>
                    <Input type="password" bind:value={pwd.password} />
                </div>
                <div>
                    <div class="text-xs text-muted-foreground mb-1">Conferma nuova password</div>
                    <Input type="password" bind:value={pwd.password_confirmation} />
                </div>
                <Button onclick={savePassword}>Salva password</Button>
            </Card.Content>
        </Card.Root>
    </div>
</MemberLayout>


