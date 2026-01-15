<script lang="ts">
  import { cn } from "@/lib/utils/cn";
  let { class: className = "", children } = $props<any>();
</script>

<div class={cn("flex flex-col-reverse gap-2 sm:flex-row sm:justify-end", className)}>
  {@render children?.()}
</div>


