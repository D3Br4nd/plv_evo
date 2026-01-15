<script>
	import DotsVerticalIcon from "@tabler/icons-svelte/icons/dots-vertical";
	import LogoutIcon from "@tabler/icons-svelte/icons/logout";
	import UserCircleIcon from "@tabler/icons-svelte/icons/user-circle";
	import { router } from "@inertiajs/svelte";
	import * as Avatar from "@/lib/components/ui/avatar/index.js";
	import * as DropdownMenu from "@/lib/components/ui/dropdown-menu/index.js";
	import * as Sidebar from "@/lib/components/ui/sidebar/index.js";

	let { user } = $props();

	const sidebar = Sidebar.useSidebar();

	function initials(name) {
		if (!name) return "U";
		return name
			.split(" ")
			.filter(Boolean)
			.map((n) => n[0])
			.join("")
			.toUpperCase()
			.slice(0, 2);
	}
</script>

<Sidebar.Menu>
	<Sidebar.MenuItem>
		<DropdownMenu.Root>
			<DropdownMenu.Trigger asChild>
				{#snippet child({ props })}
					<Sidebar.MenuButton
						{...props}
						size="lg"
						class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
					>
						<Avatar.Root class="size-8 rounded-lg grayscale">
							<Avatar.Image
								src={user?.avatar_url}
								alt={user?.name}
							/>
							<Avatar.Fallback class="rounded-lg"
								>{initials(user?.name)}</Avatar.Fallback
							>
						</Avatar.Root>
						<div
							class="grid flex-1 text-start text-sm leading-tight"
						>
							<span class="truncate font-medium"
								>{user?.name || "Utente"}</span
							>
							<span
								class="text-muted-foreground truncate text-xs"
							>
								{user?.email || ""}
							</span>
						</div>
						<DotsVerticalIcon class="ms-auto size-4" />
					</Sidebar.MenuButton>
				{/snippet}
			</DropdownMenu.Trigger>
			<DropdownMenu.Content
				class="w-(--bits-dropdown-menu-anchor-width) min-w-56 rounded-lg"
				side={sidebar.isMobile ? "bottom" : "right"}
				align="end"
				sideOffset={4}
			>
				<DropdownMenu.Label class="p-0 font-normal">
					<div
						class="flex items-center gap-2 px-1 py-1.5 text-start text-sm"
					>
						<Avatar.Root class="size-8 rounded-lg">
							<Avatar.Image
								src={user?.avatar_url}
								alt={user?.name}
							/>
							<Avatar.Fallback class="rounded-lg"
								>{initials(user?.name)}</Avatar.Fallback
							>
						</Avatar.Root>
						<div
							class="grid flex-1 text-start text-sm leading-tight"
						>
							<span class="truncate font-medium"
								>{user?.name || "Utente"}</span
							>
							<span
								class="text-muted-foreground truncate text-xs"
							>
								{user?.email || ""}
							</span>
						</div>
					</div>
				</DropdownMenu.Label>
				<DropdownMenu.Separator />
				<DropdownMenu.Group>
					<DropdownMenu.Item
						onSelect={() => router.get("/admin/profile")}
					>
						<UserCircleIcon />
						Profilo e password
					</DropdownMenu.Item>
					<DropdownMenu.Item onSelect={() => router.get("/ui/me")}>
						Modalità socio
					</DropdownMenu.Item>
				</DropdownMenu.Group>
				<DropdownMenu.Separator />
				<DropdownMenu.Item onSelect={() => router.post("/logout")}>
					<LogoutIcon />
					Esci
				</DropdownMenu.Item>
			</DropdownMenu.Content>
		</DropdownMenu.Root>
	</Sidebar.MenuItem>
</Sidebar.Menu>
