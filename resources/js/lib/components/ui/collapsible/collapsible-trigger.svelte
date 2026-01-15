<script>
	import { Collapsible as CollapsiblePrimitive } from "bits-ui";

	let { ref = $bindable(null), children, child, ...restProps } = $props();
</script>

<CollapsiblePrimitive.Trigger
	bind:ref
	data-slot="collapsible-trigger"
	{...restProps}
>
	{#snippet child({ props })}
		{#if child}
			{@render child({ props })}
		{:else}
			{@render children?.({ builder: props })}
		{/if}
	{/snippet}
</CollapsiblePrimitive.Trigger>
