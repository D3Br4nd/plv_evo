<script lang="ts">
  import { Select } from "bits-ui";
  import { cn } from "@/lib/utils/cn";
  import Check from "lucide-svelte/dist/icons/check.svelte";

  // Shows a check indicator when selected via data attribute.
  let { class: className = "", children, ...rest } = $props<any>();
</script>

<Select.Item
  {...rest}
  class={cn(
    "group relative flex w-full cursor-default select-none items-center rounded-sm py-1.5 pl-2 pr-8 text-sm outline-none data-highlighted:bg-accent data-highlighted:text-accent-foreground data-disabled:pointer-events-none data-disabled:opacity-50",
    className,
  )}
>
  {@render children?.()}
  <span
    class="absolute right-2 flex size-4 items-center justify-center opacity-0 group-data-[selected]:opacity-100"
  >
    <Check class="size-4" />
  </span>
</Select.Item>


