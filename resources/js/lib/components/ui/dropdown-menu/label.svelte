<script lang="ts">
  import { DropdownMenu } from "bits-ui";
  import { cn } from "@/lib/utils/cn";
  let { class: className = "", ...rest } = $props<any>();
</script>

<DropdownMenu.GroupHeading
  {...rest}
  class={cn("px-2 py-1.5 text-xs font-semibold text-muted-foreground", className)}
/>


