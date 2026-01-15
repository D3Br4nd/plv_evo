<script lang="ts">
  import { Select } from "bits-ui";
  import { cn } from "@/lib/utils/cn";
  import ChevronDown from "lucide-svelte/dist/icons/chevron-down.svelte";
  let { class: className = "", children, ...rest } = $props<any>();
</script>

<Select.ScrollDownButton
  {...rest}
  class={cn("flex cursor-default items-center justify-center py-1", className)}
>
  {#if children}
    {@render children?.()}
  {:else}
    <ChevronDown class="size-4" />
  {/if}
</Select.ScrollDownButton>


