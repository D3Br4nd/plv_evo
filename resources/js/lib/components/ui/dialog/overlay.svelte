<script lang="ts">
  import { Dialog } from "bits-ui";
  import { cn } from "@/lib/utils/cn";

  let { class: className = "", ...rest } = $props<any>();
</script>

<Dialog.Overlay
  {...rest}
  class={cn(
    "fixed inset-0 z-50 bg-black/50 backdrop-blur-sm data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0",
    className,
  )}
/>


