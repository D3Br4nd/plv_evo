<script lang="ts">
  import { cn } from "@/lib/utils/cn";
  let { class: className = "", children } = $props<{ class?: string; children?: any }>();
</script>

<tbody class={cn("[&_tr:last-child]:border-0", className)}>
  {@render children?.()}
</tbody>


