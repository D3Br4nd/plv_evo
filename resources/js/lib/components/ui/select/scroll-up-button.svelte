<script lang="ts">
  import { Select } from "bits-ui";
  import { cn } from "@/lib/utils/cn";
  import ChevronUp from "lucide-svelte/dist/icons/chevron-up.svelte";
  let { class: className = "", children, ...rest } = $props<any>();
</script>

<Select.ScrollUpButton
  {...rest}
  class={cn("flex cursor-default items-center justify-center py-1", className)}
>
  {#if children}
    {@render children?.()}
  {:else}
    <ChevronUp class="size-4" />
  {/if}
</Select.ScrollUpButton>


