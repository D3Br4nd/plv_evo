<script>
	import { DropdownMenu as DropdownMenuPrimitive } from "bits-ui";

	let { open = $bindable(false), children, ...restProps } = $props();
</script>

<DropdownMenuPrimitive.Sub bind:open {...restProps}>
	{@render children?.()}
</DropdownMenuPrimitive.Sub>