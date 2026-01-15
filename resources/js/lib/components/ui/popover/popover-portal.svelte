<script>
	import { Popover as PopoverPrimitive } from "bits-ui";

	let { children, ...restProps } = $props();
</script>

<PopoverPrimitive.Portal {...restProps}>
	{@render children?.()}
</PopoverPrimitive.Portal>