<script>
	import { Select as SelectPrimitive } from "bits-ui";

	let { ref = $bindable(null), children, ...restProps } = $props();
</script>

<SelectPrimitive.Group bind:ref data-slot="select-group" {...restProps}>
	{@render children?.()}
</SelectPrimitive.Group>