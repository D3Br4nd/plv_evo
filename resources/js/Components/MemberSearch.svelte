<script>
    import { Button } from "@/lib/components/ui/button";
    import * as Command from "@/lib/components/ui/command";
    import * as Popover from "@/lib/components/ui/popover";
    import { cn } from "@/lib/utils";
    import ChevronsUpDown from "lucide-svelte/icons/chevrons-up-down";
    import Check from "lucide-svelte/icons/check";
    import Search from "lucide-svelte/icons/search";

    let { onSelect } = $props();

    let open = $state(false);
    let value = $state("");
    let loading = $state(false);
    let members = $state([]);
    let searchTerm = $state("");
    let debounceTimer = $state(null);

    function handleSearch(term) {
        searchTerm = term;
        if (debounceTimer) clearTimeout(debounceTimer);

        if (term.length < 2) {
            members = [];
            return;
        }

        loading = true;
        debounceTimer = setTimeout(async () => {
            try {
                const res = await fetch(
                    `/admin/members/search?q=${encodeURIComponent(term)}`,
                );
                if (res.ok) {
                    members = await res.json();
                }
            } catch (e) {
                console.error(e);
            } finally {
                loading = false;
            }
        }, 300);
    }

    function handleSelect(memberId) {
        value = memberId;
        open = false;
        onSelect(memberId);
    }
</script>

<Popover.Root bind:open>
    <Popover.Trigger asChild>
        {#snippet children({ builder })}
            <Button
                {...builder}
                variant="outline"
                role="combobox"
                aria-expanded={open}
                class="w-full justify-between"
                disabled={false}
            >
                <span class="truncate">
                    {value
                        ? (members.find((m) => m.id === value)?.name ??
                          "Selezionato")
                        : "Cerca socio..."}
                </span>
                <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
            </Button>
        {/snippet}
    </Popover.Trigger>
    <Popover.Content class="w-[300px] p-0" align="start">
        <Command.Root shouldFilter={false}>
            <div class="flex items-center border-b px-3" cmdk-input-wrapper="">
                <Search class="mr-2 h-4 w-4 shrink-0 opacity-50" />
                <input
                    id="member-search-input"
                    class="flex h-11 w-full rounded-md bg-transparent py-3 text-sm outline-none placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50"
                    placeholder="Digita nome o email..."
                    oninput={(e) => handleSearch(e.target.value)}
                />
            </div>

            <Command.List>
                {#if loading}
                    <Command.Loading>Cercando...</Command.Loading>
                {/if}

                {#if !loading && searchTerm.length >= 2 && members.length === 0}
                    <Command.Empty>Nessun socio trovato.</Command.Empty>
                {/if}

                {#if !loading && searchTerm.length < 2}
                    <div class="py-6 text-center text-sm text-muted-foreground">
                        Digita almeno 2 caratteri...
                    </div>
                {/if}

                <Command.Group>
                    {#each members as member (member.id)}
                        <Command.Item
                            value={member.id}
                            onSelect={() => handleSelect(member.id)}
                        >
                            <Check
                                class={cn(
                                    "mr-2 h-4 w-4",
                                    value === member.id
                                        ? "opacity-100"
                                        : "opacity-0",
                                )}
                            />
                            <div class="flex flex-col">
                                <span>{member.name}</span>
                                <span class="text-xs text-muted-foreground"
                                    >{member.email}</span
                                >
                            </div>
                            {#if member.status === "active"}
                                <span
                                    class="ml-auto text-xs text-green-600 font-medium"
                                    >Attivo</span
                                >
                            {:else}
                                <span class="ml-auto text-xs text-red-500"
                                    >Inattivo</span
                                >
                            {/if}
                        </Command.Item>
                    {/each}
                </Command.Group>
            </Command.List>
        </Command.Root>
    </Popover.Content>
</Popover.Root>
