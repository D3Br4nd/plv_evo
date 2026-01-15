<script>
	import { Collapsible as CollapsiblePrimitive } from "bits-ui";

	let {
		ref = $bindable(null),
		open = $bindable(false),
		children,
		...restProps
	} = $props();
</script>

<CollapsiblePrimitive.Root bind:ref bind:open data-slot="collapsible" {...restProps}>
	{@render children?.()}
</CollapsiblePrimitive.Root>