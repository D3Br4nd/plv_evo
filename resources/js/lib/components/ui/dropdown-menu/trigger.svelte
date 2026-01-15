<script lang="ts">
  import { DropdownMenu } from "bits-ui";
  import { cn } from "@/lib/utils/cn";

  // Supports Bits UI "child" snippet for render delegation.
  let { class: className = "", ...rest } = $props<any>();
</script>

<DropdownMenu.Trigger
  {...rest}
  class={cn("outline-none", className)}
/>


