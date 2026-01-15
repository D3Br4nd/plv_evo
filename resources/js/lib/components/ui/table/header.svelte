<script lang="ts">
  import { cn } from "@/lib/utils/cn";
  let { class: className = "", children } = $props<{ class?: string; children?: any }>();
</script>

<thead class={cn("[&_tr]:border-b", className)}>
  {@render children?.()}
</thead>


