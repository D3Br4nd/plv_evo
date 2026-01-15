<script>
	import { Popover as PopoverPrimitive } from "bits-ui";

	let { ref = $bindable(null), children, child, ...restProps } = $props();
</script>

<PopoverPrimitive.Trigger bind:ref data-slot="popover-trigger" {...restProps}>
	{#snippet child({ props })}
		{#if child}
			{@render child({ props })}
		{:else}
			{@render children?.({ builder: props })}
		{/if}
	{/snippet}
</PopoverPrimitive.Trigger>
