<script lang="ts">
  import { Dialog } from "bits-ui";

  // Pass-through wrapper for Bits UI Dialog.Root
  let { open = $bindable(false), children, ...rest } = $props<any>();
</script>

<Dialog.Root bind:open {...rest}>
  {@render children?.()}
</Dialog.Root>
