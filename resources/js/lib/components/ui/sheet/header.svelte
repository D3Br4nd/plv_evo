<script lang="ts">
  import { cn } from "@/lib/utils/cn";
  let { class: className = "", children } = $props<any>();
</script>

<div class={cn("flex flex-col space-y-1.5 text-center sm:text-left", className)}>
  {@render children?.()}
</div>


