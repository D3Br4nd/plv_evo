<script>
    /* eslint-disable */
    import { page } from "@inertiajs/svelte";
    import { Link } from "@inertiajs/svelte";
    import { Home, CalendarDays, User, Bell } from "lucide-svelte";
    import { cn } from "@/lib/utils/cn";
    import { buttonVariants } from "@/lib/components/ui/button";

    // Use derived directly from the store to ensure reactivity
    let currentPath = $derived($page.url ? $page.url.split("?")[0] : "");

    function isActive(href) {
        if (href === "/me") return currentPath === "/me";
        return currentPath.startsWith(href);
    }

    const items = [
        { href: "/me", label: "Home", icon: Home },
        { href: "/me/committees", label: "Comitati", icon: User },
        { href: "/me/events", label: "Eventi", icon: CalendarDays },
        { href: "/me/notifications", label: "Notifiche", icon: Bell },
        { href: "/me/profile", label: "Profilo", icon: User },
    ];
</script>

<nav
    class="fixed bottom-0 left-0 right-0 z-40 border-t bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/80"
    style="padding-bottom: env(safe-area-inset-bottom);"
>
    <div class="mx-auto grid max-w-xl grid-cols-5 gap-1 px-2 py-2">
        {#each items as item (item.href)}
            <Link
                href={item.href}
                class={cn(
                    buttonVariants({ variant: "ghost" }),
                    "h-12 flex-col gap-1 text-xs",
                    isActive(item.href)
                        ? "!text-blue-600 font-medium"
                        : "text-muted-foreground",
                )}
            >
                <item.icon class="size-5" />
                <span>{item.label}</span>
            </Link>
        {/each}
    </div>
</nav>
