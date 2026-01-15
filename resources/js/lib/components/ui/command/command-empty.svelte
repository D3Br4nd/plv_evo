<script>
	import { Command as CommandPrimitive } from "bits-ui";
	import { cn } from "@/lib/utils.js";

	let {
		ref = $bindable(null),
		class: className = "",
		children,
		...restProps
	} = $props();
</script>

<CommandPrimitive.Empty
	bind:ref
	data-slot="command-empty"
	class={cn("py-6 text-center text-sm", className)}
	{...restProps}
>
	{@render children?.()}
</CommandPrimitive.Empty>