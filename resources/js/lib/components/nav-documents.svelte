<script>
	import DotsIcon from "@tabler/icons-svelte/icons/dots";
	import FolderIcon from "@tabler/icons-svelte/icons/folder";
	import Share3Icon from "@tabler/icons-svelte/icons/share-3";
	import TrashIcon from "@tabler/icons-svelte/icons/trash";
	import * as DropdownMenu from "@/lib/components/ui/dropdown-menu/index.js";
	import * as Sidebar from "@/lib/components/ui/sidebar/index.js";
	import { Link } from "@inertiajs/svelte";

	let { items = [] } = $props();

	const sidebar = Sidebar.useSidebar();
</script>

<Sidebar.Group class="group-data-[collapsible=icon]:hidden">
	<Sidebar.Menu>
		{#each items as item (item.url || item.name)}
			<Sidebar.MenuItem>
				<Sidebar.MenuButton>
					{#snippet child({ props })}
						<Link {...props} href={item.url}>
							{#if item.icon}
								<item.icon />
							{/if}
							<span>{item.name}</span>
						</Link>
					{/snippet}
				</Sidebar.MenuButton>
				<DropdownMenu.Root>
					<DropdownMenu.Trigger asChild>
						{#snippet child({ props })}
							<Sidebar.MenuAction
								{...props}
								showOnHover
								class="data-[state=open]:bg-accent rounded-sm"
							>
								<DotsIcon />
								<span class="sr-only">Azioni</span>
							</Sidebar.MenuAction>
						{/snippet}
					</DropdownMenu.Trigger>
					<DropdownMenu.Content
						class="w-24 rounded-lg"
						side={sidebar.isMobile ? "bottom" : "right"}
						align={sidebar.isMobile ? "end" : "start"}
					>
						<DropdownMenu.Item>
							<FolderIcon class="mr-2 h-4 w-4" />
							<span>Apri</span>
						</DropdownMenu.Item>
						<DropdownMenu.Item>
							<Share3Icon class="mr-2 h-4 w-4" />
							<span>Condividi</span>
						</DropdownMenu.Item>
						<DropdownMenu.Separator />
						<DropdownMenu.Item variant="destructive">
							<TrashIcon class="mr-2 h-4 w-4" />
							<span>Elimina</span>
						</DropdownMenu.Item>
					</DropdownMenu.Content>
				</DropdownMenu.Root>
			</Sidebar.MenuItem>
		{/each}
	</Sidebar.Menu>
</Sidebar.Group>
