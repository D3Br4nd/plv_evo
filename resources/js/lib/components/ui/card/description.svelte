<script lang="ts">
  import { cn } from "@/lib/utils/cn";
  let { class: className = "", children } = $props<{ class?: string; children?: any }>();
</script>

<p class={cn("text-sm text-muted-foreground", className)}>
  {@render children?.()}
</p>


