<script>
    /* eslint-disable */
    import * as Sidebar from "@/lib/components/ui/sidebar/index.js";
    import AppSidebar from "@/lib/components/app-sidebar.svelte";
    import SiteHeader from "@/lib/components/site-header.svelte";
    import FlashToasts from "@/lib/components/FlashToasts.svelte";
    import { Toaster } from "svelte-sonner";

    let {
        title = "Admin",
        children,
        headerActions = undefined,
        breadcrumbs = [],
    } = $props();
</script>

<svelte:head>
    <title>{title} · Pro Loco Admin</title>
</svelte:head>

<Sidebar.Provider
    style="--sidebar-width: calc(var(--spacing) * 72); --header-height: calc(var(--spacing) * 12);"
>
    <FlashToasts />
    <Toaster richColors />
    <AppSidebar variant="inset" />
    <Sidebar.Inset>
        <SiteHeader {title} {headerActions} {breadcrumbs} />
        <div class="flex flex-1 flex-col">
            <div class="@container/main flex flex-1 flex-col gap-2">
                <div class="flex flex-col gap-4 py-4 md:gap-6 md:py-6">
                    <div class="px-4 lg:px-6">
                        {@render children()}
                    </div>
                </div>
            </div>
        </div>
    </Sidebar.Inset>
</Sidebar.Provider>
