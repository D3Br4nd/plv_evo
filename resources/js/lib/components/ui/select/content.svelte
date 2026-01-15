<script lang="ts">
  import { Select } from "bits-ui";
  import { cn } from "@/lib/utils/cn";

  let { class: className = "", children, ...rest } = $props<any>();
</script>

<Select.Portal>
  <Select.Content
    {...rest}
    class={cn(
      "z-50 max-h-[var(--bits-select-content-available-height)] w-[var(--bits-select-anchor-width)] overflow-hidden rounded-md border bg-popover text-popover-foreground shadow-md outline-none data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[side=bottom]:slide-in-from-top-2 data-[side=top]:slide-in-from-bottom-2",
      className,
    )}
  >
    {@render children?.()}
  </Select.Content>
</Select.Portal>


