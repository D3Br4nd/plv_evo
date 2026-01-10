<script>
    import { router, page } from "@inertiajs/svelte";
    import QRCode from "qrcode";
    import MemberLayout from "@/layouts/MemberLayout.svelte";
    import { Button } from "@/lib/components/ui/button";

    let { year, membership } = $props();
    let qrDataUrl = $state(null);
    let uuid = $derived($page.props.auth?.user?.id);

    async function generate() {
        if (!uuid) return;
        qrDataUrl = await QRCode.toDataURL(uuid, {
            width: 260,
            margin: 2,
            color: { dark: "#000000", light: "#FFFFFF" },
        });
    }

    $effect(() => {
        generate();
    });
</script>

<MemberLayout title={`Tessera ${year}`}>
    {#snippet headerActions()}
        <Button variant="outline" onclick={() => router.get("/me/onboarding")}>
            Onboarding
        </Button>
    {/snippet}

    <div class="space-y-6 max-w-md">
        <div class="text-sm text-muted-foreground">
            Mostra questo QR all’ingresso degli eventi.
        </div>

        <div class="rounded-lg border bg-card p-6 space-y-4">
            {#if membership}
                <div class="flex justify-center">
                    {#if qrDataUrl}
                        <img
                            src={qrDataUrl}
                            alt="QR Tessera"
                            class="rounded bg-white p-2"
                        />
                    {:else}
                        <div class="text-sm text-muted-foreground">
                            Generazione QR...
                        </div>
                    {/if}
                </div>

                <div class="text-xs text-muted-foreground break-all">
                    Socio UUID: {uuid}
                </div>
            {:else}
                <div class="text-sm text-red-400">
                    Nessuna tessera attiva per {year}. Contatta la segreteria.
                </div>
            {/if}
        </div>
    </div>
</MemberLayout>
