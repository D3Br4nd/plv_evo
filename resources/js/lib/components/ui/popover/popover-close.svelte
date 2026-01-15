<script>
	import { Popover as PopoverPrimitive } from "bits-ui";

	let { ref = $bindable(null), children, ...restProps } = $props();
</script>

<PopoverPrimitive.Close bind:ref data-slot="popover-close" {...restProps}>
	{@render children?.()}
</PopoverPrimitive.Close>