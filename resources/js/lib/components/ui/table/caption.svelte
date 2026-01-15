<script lang="ts">
  import { cn } from "@/lib/utils/cn";
  let { class: className = "", children } = $props<{ class?: string; children?: any }>();
</script>

<caption class={cn("mt-4 text-sm text-muted-foreground", className)}>
  {@render children?.()}
</caption>


