<script>
	import * as Sidebar from "@/lib/components/ui/sidebar/index.js";
	import * as Collapsible from "@/lib/components/ui/collapsible";
	import { Link } from "@inertiajs/svelte";
	import ChevronRightIcon from "@tabler/icons-svelte/icons/chevron-right";

	let { items = [] } = $props();
</script>

<Sidebar.Group>
	<Sidebar.GroupContent class="flex flex-col gap-2">
		<Sidebar.Menu>
			{#each items as item (item.url || item.title)}
				<Sidebar.MenuItem>
					{#if item.items && item.items.length > 0}
						<!-- Collapsible menu item with sub-items -->
						<Collapsible.Root>
							<Collapsible.Trigger asChild>
								{#snippet child({ props })}
									<Sidebar.MenuButton
										{...props}
										tooltipContent={item.title}
									>
										{#if item.icon}
											<item.icon />
										{/if}
										<span>{item.title}</span>
										<ChevronRightIcon
											class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90"
										/>
									</Sidebar.MenuButton>
								{/snippet}
							</Collapsible.Trigger>
							<Collapsible.Content>
								<Sidebar.MenuSub>
									{#each item.items as subItem (subItem.url || subItem.title)}
										<Sidebar.MenuSubItem>
											<Sidebar.MenuSubButton asChild>
												{#snippet child({ props })}
													<Link
														href={subItem.url}
														{...props}
													>
														<span
															>{subItem.title}</span
														>
													</Link>
												{/snippet}
											</Sidebar.MenuSubButton>
										</Sidebar.MenuSubItem>
									{/each}
								</Sidebar.MenuSub>
							</Collapsible.Content>
						</Collapsible.Root>
					{:else}
						<!-- Simple menu item without sub-items -->
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
					{/if}
				</Sidebar.MenuItem>
			{/each}
		</Sidebar.Menu>
	</Sidebar.GroupContent>
</Sidebar.Group>
