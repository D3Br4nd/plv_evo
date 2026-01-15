<script>
    import MemberLayout from "@/layouts/MemberLayout.svelte";
    import { Button } from "@/lib/components/ui/button";
    import { ArrowLeft } from "lucide-svelte";
    import { router } from "@inertiajs/svelte";

    let { page } = $props();
</script>

<MemberLayout title={page.title}>
    <div class="space-y-4">
        <!-- Header -->
        <div class="flex items-center gap-3">
            <Button
                variant="ghost"
                size="icon"
                onclick={() => window.history.back()}
            >
                <ArrowLeft class="size-5" />
            </Button>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-bold tracking-tight truncate">
                    {page.title}
                </h1>
            </div>
        </div>

        <div class="rounded-xl border bg-card p-6 shadow-sm">
            {#if page.excerpt}
                <p
                    class="text-sm text-muted-foreground mb-6 italic leading-relaxed border-l-2 pl-4"
                >
                    {page.excerpt}
                </p>
            {/if}

            <div
                class="prose prose-sm dark:prose-invert max-w-none leading-relaxed"
            >
                {@html page.body}
            </div>
        </div>
    </div>
</MemberLayout>

<style>
    :global(.prose ul) {
        list-style-type: disc;
        padding-left: 1.5rem;
    }
    :global(.prose ol) {
        list-style-type: decimal;
        padding-left: 1.5rem;
    }
    :global(.prose a) {
        color: hsl(var(--primary));
        text-decoration: underline;
    }
    :global(.prose p) {
        margin-bottom: 0.75rem;
    }
</style>
