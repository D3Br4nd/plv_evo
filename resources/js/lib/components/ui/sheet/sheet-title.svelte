<script>
	import { Dialog as SheetPrimitive } from "bits-ui";
	import { cn } from "@/lib/utils.js";

	let {
		ref = $bindable(null),
		class: className = "",
		children,
		...restProps
	} = $props();
</script>

<SheetPrimitive.Title
	bind:ref
	data-slot="sheet-title"
	class={cn("text-foreground font-semibold", className)}
	{...restProps}
>
	{@render children?.()}
</SheetPrimitive.Title>