<script>
	import { DropdownMenu as DropdownMenuPrimitive } from "bits-ui";

	let { children, ...restProps } = $props();
</script>

<DropdownMenuPrimitive.Portal {...restProps}>
	{@render children?.()}
</DropdownMenuPrimitive.Portal>