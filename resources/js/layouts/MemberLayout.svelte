<script>
	/* eslint-disable */
	import MemberBottomNav from "@/lib/components/member-bottom-nav.svelte";
	import FlashToasts from "@/lib/components/FlashToasts.svelte";
	import { Toaster } from "svelte-sonner";
	import ThemeToggle from "@/lib/components/theme-toggle.svelte";
	import { page } from "@inertiajs/svelte";

	let { title = "Socio", children, headerActions } = $props();
</script>

<svelte:head>
	<title>{title} · Pro Loco</title>
</svelte:head>

<div class="min-h-screen bg-background text-foreground">
	<FlashToasts />
	<Toaster richColors />

	<header class="sticky top-0 z-30 border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/80">
		<div class="mx-auto flex max-w-xl items-center justify-between gap-3 px-4 py-3">
			<div class="min-w-0">
				<div class="flex items-center gap-2">
					<img
						src="/logo.png"
						alt="Pro Loco Venticanese"
						class="h-7 w-7 shrink-0 rounded-md border bg-background object-contain"
						loading="eager"
					/>
					<div class="truncate text-base font-semibold">{title}</div>
				</div>
			</div>
			<div class="flex items-center gap-2">
				{@render headerActions?.()}
				<ThemeToggle />
			</div>
		</div>
	</header>

	<main class="mx-auto max-w-xl px-4 py-4 pb-24">
		{@render children?.()}
	</main>

	<MemberBottomNav />
</div>


