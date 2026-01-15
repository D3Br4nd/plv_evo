<script lang="ts">
  import { Dialog } from "bits-ui";
  import { cn } from "@/lib/utils/cn";

  let {
    class: className = "",
    side = "right",
    children,
    ...rest
  } = $props<any>();

  const sideClasses: Record<string, string> = {
    right:
      "inset-y-0 right-0 h-full w-3/4 border-l sm:max-w-sm data-[state=open]:slide-in-from-right data-[state=closed]:slide-out-to-right",
    left:
      "inset-y-0 left-0 h-full w-3/4 border-r sm:max-w-sm data-[state=open]:slide-in-from-left data-[state=closed]:slide-out-to-left",
    top:
      "inset-x-0 top-0 w-full border-b data-[state=open]:slide-in-from-top data-[state=closed]:slide-out-to-top",
    bottom:
      "inset-x-0 bottom-0 w-full border-t data-[state=open]:slide-in-from-bottom data-[state=closed]:slide-out-to-bottom",
  };
</script>

<Dialog.Portal>
  <Dialog.Overlay class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0" />
  <Dialog.Content
    {...rest}
    class={cn(
      "fixed z-50 bg-background p-6 shadow-lg outline-none transition data-[state=open]:animate-in data-[state=closed]:animate-out",
      sideClasses[side] ?? sideClasses.right,
      className,
    )}
  >
    {@render children?.()}
  </Dialog.Content>
</Dialog.Portal>


