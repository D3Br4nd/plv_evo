<script lang="ts">
  import { cn } from "@/lib/utils/cn";
  let { class: className = "", children } = $props<{ class?: string; children?: any }>();
</script>

<h3 class={cn("text-lg font-semibold leading-none tracking-tight", className)}>
  {@render children?.()}
</h3>


