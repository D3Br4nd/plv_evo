<script lang="ts">
  import { DropdownMenu } from "bits-ui";
  import { cn } from "@/lib/utils/cn";
  let { class: className = "", ...rest } = $props<any>();
</script>

<DropdownMenu.Separator
  {...rest}
  class={cn("-mx-1 my-1 h-px bg-border", className)}
/>


