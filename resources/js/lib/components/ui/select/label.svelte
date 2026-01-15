<script lang="ts">
  import { Select } from "bits-ui";
  import { cn } from "@/lib/utils/cn";
  let { class: className = "", children, ...rest } = $props<any>();
</script>

<Select.GroupHeading
  {...rest}
  class={cn("px-2 py-1.5 text-xs font-semibold text-muted-foreground", className)}
>
  {@render children?.()}
</Select.GroupHeading>


