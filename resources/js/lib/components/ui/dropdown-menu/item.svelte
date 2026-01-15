<script lang="ts">
  import { DropdownMenu } from "bits-ui";
  import { cn } from "@/lib/utils/cn";
  let { class: className = "", ...rest } = $props<any>();
</script>

<DropdownMenu.Item
  {...rest}
  class={cn(
    "relative flex cursor-default select-none items-center gap-2 rounded-sm px-2 py-1.5 text-sm outline-none data-highlighted:bg-accent data-highlighted:text-accent-foreground data-disabled:pointer-events-none data-disabled:opacity-50",
    className,
  )}
/>


