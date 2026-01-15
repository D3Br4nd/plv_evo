<script>
	import { Select as SelectPrimitive } from "bits-ui";

	let {
		open = $bindable(false),
		value = $bindable(""),
		children,
		...restProps
	} = $props();
</script>

<SelectPrimitive.Root bind:open bind:value {...restProps}>
	{@render children?.()}
</SelectPrimitive.Root>
