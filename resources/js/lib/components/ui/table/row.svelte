<script lang="ts">
  import { cn } from "@/lib/utils/cn";
  let { class: className = "", children } = $props<{ class?: string; children?: any }>();
</script>

<tr class={cn("border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted", className)}>
  {@render children?.()}
</tr>


