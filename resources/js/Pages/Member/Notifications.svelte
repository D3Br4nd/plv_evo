<script>
    /* eslint-disable */
    import MemberLayout from "@/layouts/MemberLayout.svelte";
    import * as Card from "@/lib/components/ui/card";
    import { Button } from "@/lib/components/ui/button";
    import { Badge } from "@/lib/components/ui/badge";
    import { router, Link } from "@inertiajs/svelte";
    import { toast } from "svelte-sonner";

    let { notifications, vapidPublicKey, hasPushSubscription } = $props();

    let pushEnabled = $state(false);
    let processing = $state(false);

    $effect(() => {
        pushEnabled = !!hasPushSubscription;
    });

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

    async function enablePush() {
        if (!("Notification" in window) || !("serviceWorker" in navigator)) {
            toast.error("Notifiche non supportate su questo browser.");
            return;
        }
        if (!vapidPublicKey) {
            toast.error("VAPID_PUBLIC_KEY non configurata sul server.");
            return;
        }
        processing = true;
        try {
            const permission = await Notification.requestPermission();
            if (permission !== "granted") {
                toast.message("Permesso notifiche non concesso.");
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
                toast.error("Errore durante la registrazione sul server.");
                return;
            }
            pushEnabled = true;
            toast.success("Notifiche abilitate.");
        } catch (e) {
            toast.error("Impossibile abilitare le notifiche.");
        } finally {
            processing = false;
        }
    }

    async function disablePush() {
        if (!("serviceWorker" in navigator)) {
            toast.error("Notifiche non supportate su questo browser.");
            return;
        }
        processing = true;
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
                toast.error("Errore durante la disattivazione sul server.");
                return;
            }
            pushEnabled = false;
            toast.message("Notifiche disabilitate.");
        } catch (e) {
            toast.error("Impossibile disabilitare le notifiche.");
        } finally {
            processing = false;
        }
    }

    function destroyNotification(id) {
        router.delete(`/me/notifications/${id}`, { preserveScroll: true });
    }

    function titleOf(n) {
        const data = n?.data || {};
        return data?.title || "Notifica";
    }

    function bodyOf(n) {
        const data = n?.data || {};
        return data?.body || data?.message || "";
    }

    function urlOf(n) {
        return n?.data?.url || null;
    }
</script>

<MemberLayout title="Notifiche">
    {#snippet headerActions()}{/snippet}

    <div class="space-y-4">
        <Card.Root>
            <Card.Header>
                <Card.Title>Notifiche</Card.Title>
                <Card.Description
                    >Le notifiche restano qui finché non le elimini.</Card.Description
                >
            </Card.Header>
            <Card.Content class="space-y-3">
                {#if notifications?.data?.length}
                    {#each notifications.data as n (n.id)}
                        <div class="rounded-lg border p-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    {#if urlOf(n)}
                                        <Link
                                            href={urlOf(n)}
                                            class="block hover:opacity-80 transition-opacity"
                                        >
                                            <div class="font-medium truncate">
                                                {titleOf(n)}
                                            </div>
                                            {#if bodyOf(n)}
                                                <div
                                                    class="mt-1 text-xs text-muted-foreground line-clamp-2"
                                                >
                                                    {bodyOf(n)}
                                                </div>
                                            {/if}
                                        </Link>
                                    {:else}
                                        <div class="font-medium truncate">
                                            {titleOf(n)}
                                        </div>
                                        {#if bodyOf(n)}
                                            <div
                                                class="mt-1 text-xs text-muted-foreground"
                                            >
                                                {bodyOf(n)}
                                            </div>
                                        {/if}
                                    {/if}
                                    <div
                                        class="mt-2 text-[11px] text-muted-foreground"
                                    >
                                        {new Date(n.created_at).toLocaleString(
                                            "it-IT",
                                        )}
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    {#if n.read_at}
                                        <Badge variant="secondary">Letta</Badge>
                                    {:else}
                                        <Badge variant="outline">Nuova</Badge>
                                    {/if}
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        onclick={() =>
                                            destroyNotification(n.id)}
                                    >
                                        Elimina
                                    </Button>
                                </div>
                            </div>
                        </div>
                    {/each}
                {:else}
                    <div class="text-sm text-muted-foreground">
                        Nessuna notifica.
                    </div>
                {/if}
            </Card.Content>
        </Card.Root>
    </div>
</MemberLayout>
