<script lang="ts">
  import { Select } from "bits-ui";
  import { cn } from "@/lib/utils/cn";
  import ChevronDown from "lucide-svelte/dist/icons/chevron-down.svelte";

  let { class: className = "", children, ...rest } = $props<any>();
</script>

<Select.Trigger
  {...rest}
  class={cn(
    "flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50",
    className,
  )}
>
  {#if children}
    {@render children?.()}
  {/if}
  <ChevronDown class="ml-2 size-4 opacity-50" />
</Select.Trigger>


