<script>
    import { Select as SelectPrimitive } from "bits-ui";
    import { cn } from "@/lib/utils.js";

    let {
        ref = $bindable(null),
        class: className = "",
        placeholder,
        ...restProps
    } = $props();
</script>

<SelectPrimitive.Value
    bind:ref
    class={cn("data-[placeholder]:text-muted-foreground", className)}
    {placeholder}
    {...restProps}
/>
