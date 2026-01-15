<script>
	import { DropdownMenu as DropdownMenuPrimitive } from "bits-ui";

	let { ref = $bindable(null), children, ...restProps } = $props();
</script>

<DropdownMenuPrimitive.Group bind:ref data-slot="dropdown-menu-group" {...restProps}>
	{@render children?.()}
</DropdownMenuPrimitive.Group>