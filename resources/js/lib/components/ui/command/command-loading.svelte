<script>
	import { Command as CommandPrimitive } from "bits-ui";

	let { ref = $bindable(null), children, ...restProps } = $props();
</script>

<CommandPrimitive.Loading bind:ref {...restProps}>
	{@render children?.()}
</CommandPrimitive.Loading>