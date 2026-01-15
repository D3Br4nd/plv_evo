<script lang="ts">
  import { cn } from "@/lib/utils/cn";
  let { class: className = "", children, ...rest } = $props<any>();
</script>

<div
  {...rest}
  class={cn("rounded-lg border bg-card text-card-foreground shadow-sm", className)}
>
  {@render children?.()}
</div>


