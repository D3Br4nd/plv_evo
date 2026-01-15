<script lang="ts">
  import { Dialog } from "bits-ui";
  import { cn } from "@/lib/utils/cn";

  // This component includes Portal + Overlay for convenience (shadcn pattern).
  let { class: className = "", children, ...rest } = $props<any>();
</script>

<Dialog.Portal>
  <Dialog.Overlay
    class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0"
  />
  <Dialog.Content
    {...rest}
    class={cn(
      "fixed left-1/2 top-1/2 z-50 w-full max-w-lg -translate-x-1/2 -translate-y-1/2 rounded-lg border bg-background p-6 shadow-lg outline-none data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95",
      className,
    )}
  >
    {@render children?.()}
  </Dialog.Content>
</Dialog.Portal>


