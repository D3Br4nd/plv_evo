<script>
	import { Tooltip as TooltipPrimitive } from "bits-ui";

	let { open = $bindable(false), children, ...restProps } = $props();
</script>

<TooltipPrimitive.Root bind:open {...restProps}>
	{@render children?.()}
</TooltipPrimitive.Root>