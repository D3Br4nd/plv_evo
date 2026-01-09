<script>
    import MemberLayout from "@/layouts/MemberLayout.svelte";
    import * as Card from "@/lib/components/ui/card";
    import { Users, ChevronRight, MessageSquare } from "lucide-svelte";
    import { Link } from "@inertiajs/svelte";
    import { Badge } from "@/lib/components/ui/badge";

    let { committees } = $props();
</script>

<MemberLayout title="I Miei Comitati">
    <div class="space-y-4">
        <div class="flex flex-col gap-1">
            <h2 class="text-2xl font-bold tracking-tight">I Miei Comitati</h2>
            <p class="text-muted-foreground">
                I gruppi di cui fai parte nell'associazione.
            </p>
        </div>

        {#if committees.length === 0}
            <Card.Root class="border-dashed">
                <Card.Content class="pt-6 text-center">
                    <Users
                        class="mx-auto size-12 text-muted-foreground/50 mb-3"
                    />
                    <p class="text-muted-foreground">
                        Non sei ancora parte di alcun comitato.
                    </p>
                </Card.Content>
            </Card.Root>
        {:else}
            <div class="grid gap-4">
                {#each committees as committee}
                    <Link href="/me/committees/{committee.id}" class="block">
                        <Card.Root class="hover:bg-accent/50 transition-colors">
                            <Card.Header
                                class="p-4 flex flex-row items-center justify-between space-y-0"
                            >
                                <div class="flex items-center gap-4">
                                    <div
                                        class="size-12 rounded-xl bg-muted flex items-center justify-center overflow-hidden border"
                                    >
                                        {#if committee.image_url}
                                            <img
                                                src={committee.image_url}
                                                alt={committee.name}
                                                class="h-full w-full object-cover"
                                            />
                                        {:else}
                                            <Users
                                                class="size-6 text-muted-foreground"
                                            />
                                        {/if}
                                    </div>
                                    <div class="flex flex-col">
                                        <Card.Title class="text-base"
                                            >{committee.name}</Card.Title
                                        >
                                        <div
                                            class="flex items-center gap-2 mt-1"
                                        >
                                            {#if committee.unread_posts_count > 0}
                                                <Badge
                                                    variant="destructive"
                                                    class="h-5 px-1.5 text-[10px]"
                                                >
                                                    {committee.unread_posts_count}
                                                    nuovi
                                                </Badge>
                                            {/if}
                                            <span
                                                class="text-xs text-muted-foreground capitalize"
                                                >{committee.pivot.role ||
                                                    "Membro"}</span
                                            >
                                        </div>
                                    </div>
                                </div>
                                <ChevronRight
                                    class="size-5 text-muted-foreground"
                                />
                            </Card.Header>
                        </Card.Root>
                    </Link>
                {/each}
            </div>
        {/if}
    </div>
</MemberLayout>
