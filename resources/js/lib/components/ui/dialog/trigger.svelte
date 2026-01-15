<script lang="ts">
  import { Dialog } from "bits-ui";
  let { children, child, ...rest } = $props<any>();
</script>

<Dialog.Trigger {...rest}>
  {#snippet child({ props })}
    {#if child}
      {@render child({ props })}
    {:else}
      {@render children?.({ builder: props })}
    {/if}
  {/snippet}
</Dialog.Trigger>
