<script>
    /* eslint-disable */
    import MemberLayout from "@/layouts/MemberLayout.svelte";
    import { page } from "@inertiajs/svelte";
    import { Button } from "@/lib/components/ui/button";
    import { QrCode, CalendarDays } from "lucide-svelte";
    import { cn } from "@/lib/utils/cn";
    import { buttonVariants } from "@/lib/components/ui/button";
    import { Link } from "@inertiajs/svelte";

    let user = $derived($page.props.auth?.user);

    function greeting() {
        const h = new Date().getHours();
        if (h < 12) return "Buongiorno";
        if (h < 18) return "Buon pomeriggio";
        return "Buonasera";
    }

    function fullName(u) {
        const first = u?.first_name?.trim?.() || "";
        const last = u?.last_name?.trim?.() || "";
        const composed = `${first} ${last}`.trim();
        return composed || u?.name || "socio";
    }
</script>

<MemberLayout title="Home">
    <div class="space-y-4">
        <div class="rounded-xl border bg-card p-4">
            <div class="text-sm">
                <span class="text-muted-foreground">{greeting()}</span>
                <span class="font-medium"> {fullName(user)}!</span>
            </div>
        </div>

        <Link
            href="/me/uuid"
            class={cn(
                "block rounded-xl bg-primary text-primary-foreground",
                "shadow-sm ring-1 ring-primary/20",
                "focus:outline-none focus:ring-2 focus:ring-ring"
            )}
        >
            <div class="flex items-center gap-4 px-4 py-4">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-foreground/10">
                    <QrCode class="size-6" />
                </div>
                <div class="min-w-0">
                    <div class="font-semibold">Il mio QR (UUID)</div>
                    <div class="text-xs text-primary-foreground/80">Mostra il QR del tuo UUIDv7</div>
                </div>
            </div>
        </Link>

        <Link
            href="/me/events"
            class={cn(
                "block rounded-xl border bg-card hover:bg-accent/40 transition-colors",
                "focus:outline-none focus:ring-2 focus:ring-ring"
            )}
        >
            <div class="flex items-center gap-4 px-4 py-4">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg border bg-background">
                    <CalendarDays class="size-6" />
                </div>
                <div class="min-w-0">
                    <div class="font-semibold">Calendario eventi</div>
                    <div class="text-xs text-muted-foreground">Vista mensile ottimizzata per mobile</div>
                </div>
            </div>
        </Link>
    </div>
</MemberLayout>


