<script lang="ts">
  import { cn } from "@/lib/utils/cn";
  let { class: className = "", colspan, children } = $props<{
    class?: string;
    colspan?: number;
    children?: any;
  }>();
</script>

<td
  colspan={colspan}
  class={cn("p-4 align-middle [&:has([role=checkbox])]:pr-0", className)}
>
  {@render children?.()}
</td>


