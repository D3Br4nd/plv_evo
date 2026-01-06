<script>
	import * as Sidebar from "@/lib/components/ui/sidebar/index.js";
	import { Link } from "@inertiajs/svelte";
	let { items } = $props();
</script>

<Sidebar.Group>
	<Sidebar.GroupContent class="flex flex-col gap-2">
		<Sidebar.Menu>
			{#each items as item (item.title)}
				<Sidebar.MenuItem>
					<Sidebar.MenuButton tooltipContent={item.title}>
						{#snippet child({ props })}
							<Link href={item.url} {...props}>
						{#if item.icon}
							<item.icon />
						{/if}
						<span>{item.title}</span>
							</Link>
						{/snippet}
					</Sidebar.MenuButton>
				</Sidebar.MenuItem>
			{/each}
		</Sidebar.Menu>
	</Sidebar.GroupContent>
</Sidebar.Group>