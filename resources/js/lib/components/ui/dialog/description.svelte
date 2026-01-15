<script lang="ts">
  import { Dialog } from "bits-ui";
  import { cn } from "@/lib/utils/cn";
  	let { class: className = "", children, ...rest } = $props<any>();
</script>

<Dialog.Description
  {...rest}
  class={cn("text-sm text-muted-foreground", className)}
>
	{@render children?.()}
</Dialog.Description>
