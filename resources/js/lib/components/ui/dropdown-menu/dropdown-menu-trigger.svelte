<script>
	import { DropdownMenu as DropdownMenuPrimitive } from "bits-ui";

	let { ref = $bindable(null), children, child, ...restProps } = $props();
</script>

<DropdownMenuPrimitive.Trigger
	bind:ref
	data-slot="dropdown-menu-trigger"
	{...restProps}
>
	{#snippet child({ props })}
		{#if child}
			{@render child({ props })}
		{:else}
			{@render children?.({ builder: props })}
		{/if}
	{/snippet}
</DropdownMenuPrimitive.Trigger>
