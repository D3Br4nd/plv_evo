<script lang="ts">
  import { cn } from "@/lib/utils/cn";

  type Props = {
    class?: string;
    value?: string;
    placeholder?: string;
    id?: string;
    name?: string;
    disabled?: boolean;
    required?: boolean;
    rows?: number;
    [key: string]: any;
  };

  // Svelte 5: support `bind:value` from parents
  let {
    class: className = "",
    rows = 4,
    value = $bindable(""),
    ...rest
  } = $props() as Props;
</script>

<textarea
  {...rest}
  {rows}
  bind:value
  class={cn(
    "flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50",
    className,
  )}
></textarea>
