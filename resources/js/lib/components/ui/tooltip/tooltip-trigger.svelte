<script>
	import { Tooltip as TooltipPrimitive } from "bits-ui";

	let { ref = $bindable(null), children, child, ...restProps } = $props();
</script>

<TooltipPrimitive.Trigger bind:ref data-slot="tooltip-trigger" {...restProps}>
	{#snippet child({ props })}
		{#if child}
			{@render child({ props })}
		{:else}
			{@render children?.({ builder: props })}
		{/if}
	{/snippet}
</TooltipPrimitive.Trigger>
