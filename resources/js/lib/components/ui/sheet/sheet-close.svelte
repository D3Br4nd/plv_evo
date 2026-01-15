<script>
	import { Dialog as SheetPrimitive } from "bits-ui";

	let { ref = $bindable(null), children, ...restProps } = $props();
</script>

<SheetPrimitive.Close bind:ref data-slot="sheet-close" {...restProps}>
	{@render children?.()}
</SheetPrimitive.Close>