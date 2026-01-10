<script>
    import MemberLayout from "@/layouts/MemberLayout.svelte";
    import * as Card from "@/lib/components/ui/card";
    import * as Tabs from "@/lib/components/ui/tabs";
    import {
        Avatar,
        AvatarFallback,
        AvatarImage,
    } from "@/lib/components/ui/avatar";
    import { Badge } from "@/lib/components/ui/badge";
    import {
        Users,
        MessageSquare,
        Info,
        Clock,
        CheckCircle2,
    } from "lucide-svelte";
    import { router } from "@inertiajs/svelte";
    import { formatDistanceToNow } from "date-fns";
    import { it } from "date-fns/locale";
    import { onMount } from "svelte";

    let { committee } = $props();

    // Automatically mark unread posts as read when entering the message board
    function markUnreadAsRead() {
        const unreadPosts = committee.posts.filter((p) => !p.is_read);
        unreadPosts.forEach((post) => {
            router.post(
                `/me/committees/posts/${post.id}/read`,
                {},
                {
                    preserveScroll: true,
                    only: ["committee"],
                },
            );
        });
    }

    onMount(() => {
        // We could mark as read either on mount or when switching tabs
        // Let's do it on mount if the message board is visible or when tab changes
    });
</script>

<MemberLayout title={committee.name}>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center gap-4">
            <div
                class="size-16 rounded-2xl bg-muted flex items-center justify-center overflow-hidden border shadow-sm"
            >
                {#if committee.image_url}
                    <img
                        src={committee.image_url}
                        alt={committee.name}
                        class="h-full w-full object-cover"
                    />
                {:else}
                    <Users class="size-8 text-muted-foreground" />
                {/if}
            </div>
            <div>
                <h2 class="text-2xl font-bold tracking-tight">
                    {committee.name}
                </h2>
                <Badge variant="outline" class="mt-1">
                    {committee.status === "active" ? "Attivo" : "Inattivo"}
                </Badge>
            </div>
        </div>

        <Tabs.Root value="board" class="w-full">
            <Tabs.List class="grid w-full grid-cols-3">
                <Tabs.Trigger value="board" class="flex items-center gap-2">
                    <MessageSquare class="size-4" />
                    <span>Bacheca</span>
                </Tabs.Trigger>
                <Tabs.Trigger value="members" class="flex items-center gap-2">
                    <Users class="size-4" />
                    <span>Soci</span>
                </Tabs.Trigger>
                <Tabs.Trigger value="info" class="flex items-center gap-2">
                    <Info class="size-4" />
                    <span>Info</span>
                </Tabs.Trigger>
            </Tabs.List>

            <!-- Bacheca (Message Board) -->
            <Tabs.Content value="board" class="mt-4">
                {#if committee.posts.length === 0}
                    <div class="py-12 text-center">
                        <MessageSquare
                            class="mx-auto size-12 text-muted-foreground/30 mb-3"
                        />
                        <p class="text-muted-foreground">
                            Ancora nessun messaggio in bacheca.
                        </p>
                    </div>
                {:else}
                    <div class="space-y-4">
                        {#each committee.posts as post}
                            <Card.Root
                                class={!post.is_read
                                    ? "border-primary ring-1 ring-primary/20 bg-primary/5"
                                    : ""}
                            >
                                <Card.Header class="p-4 pb-2">
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <div class="flex items-center gap-2">
                                            <Avatar class="size-8">
                                                <AvatarImage
                                                    src={post.author.avatar_url}
                                                />
                                                <AvatarFallback
                                                    class="text-[10px]"
                                                >
                                                    {post.author.name.charAt(0)}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div class="flex flex-col">
                                                <span
                                                    class="text-sm font-semibold"
                                                    >{post.author.name}</span
                                                >
                                                <span
                                                    class="text-[10px] text-muted-foreground"
                                                >
                                                    {formatDistanceToNow(
                                                        new Date(
                                                            post.created_at,
                                                        ),
                                                        {
                                                            addSuffix: true,
                                                            locale: it,
                                                        },
                                                    )}
                                                </span>
                                            </div>
                                        </div>
                                        {#if !post.is_read}
                                            <div
                                                class="size-2 rounded-full bg-primary"
                                                title="Non letto"
                                            ></div>
                                        {:else}
                                            <CheckCircle2
                                                class="size-3 text-muted-foreground/50"
                                            />
                                        {/if}
                                    </div>
                                    <Card.Title class="text-base mt-3"
                                        >{post.title}</Card.Title
                                    >
                                </Card.Header>
                                <Card.Content
                                    class="p-4 pt-0 text-sm leading-relaxed"
                                >
                                    {post.content}
                                </Card.Content>
                                {#if !post.is_read}
                                    <Card.Footer
                                        class="p-2 border-t bg-primary/5"
                                    >
                                        <button
                                            class="w-full text-[10px] font-bold uppercase tracking-wider text-primary py-1"
                                            onclick={() => {
                                                router.post(
                                                    `/me/committees/posts/${post.id}/read`,
                                                    {},
                                                    {
                                                        preserveScroll: true,
                                                    },
                                                );
                                            }}
                                        >
                                            Segna come letto
                                        </button>
                                    </Card.Footer>
                                {/if}
                            </Card.Root>
                        {/each}
                    </div>
                {/if}
            </Tabs.Content>

            <!-- Soci (Members) -->
            <Tabs.Content value="members" class="mt-4">
                <Card.Root>
                    <Card.Content class="p-0">
                        <div class="divide-y">
                            {#each committee.members as member}
                                <div
                                    class="flex items-center justify-between p-4"
                                >
                                    <div class="flex items-center gap-3">
                                        <Avatar class="size-10">
                                            <AvatarImage
                                                src={member.avatar_url}
                                            />
                                            <AvatarFallback>
                                                {member.name.charAt(0)}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div class="flex flex-col">
                                            <span class="text-sm font-medium"
                                                >{member.name}</span
                                            >
                                            <span
                                                class="text-xs text-muted-foreground capitalize"
                                            >
                                                {member.pivot.role || "Membro"}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            {/each}
                        </div>
                    </Card.Content>
                </Card.Root>
            </Tabs.Content>

            <!-- Info (About) -->
            <Tabs.Content value="info" class="mt-4">
                <Card.Root>
                    <Card.Header>
                        <Card.Title>Informazioni</Card.Title>
                    </Card.Header>
                    <Card.Content class="space-y-4">
                        <div>
                            <h4 class="text-sm font-semibold mb-1">
                                Descrizione
                            </h4>
                            <p
                                class="text-sm text-muted-foreground leading-relaxed"
                            >
                                {committee.description ||
                                    "Nessuna descrizione disponibile."}
                            </p>
                        </div>
                        <div class="grid grid-cols-2 gap-4 pt-2">
                            <div class="bg-muted p-3 rounded-xl">
                                <span
                                    class="text-[10px] font-bold uppercase text-muted-foreground block mb-1"
                                    >Creato il</span
                                >
                                <span class="text-sm"
                                    >{new Date(
                                        committee.created_at,
                                    ).toLocaleDateString("it-IT")}</span
                                >
                            </div>
                            <div class="bg-muted p-3 rounded-xl">
                                <span
                                    class="text-[10px] font-bold uppercase text-muted-foreground block mb-1"
                                    >Totale Soci</span
                                >
                                <span class="text-sm"
                                    >{committee.members.length}</span
                                >
                            </div>
                        </div>
                    </Card.Content>
                </Card.Root>
            </Tabs.Content>
        </Tabs.Root>
    </div>
</MemberLayout>
