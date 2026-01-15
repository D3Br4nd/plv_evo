<script lang="ts">
  import { Dialog } from "bits-ui";
  import { cn } from "@/lib/utils/cn";
  	let { class: className = "", children, ...rest } = $props<any>();
</script>

<Dialog.Title
  {...rest}
  class={cn("text-lg font-semibold leading-none tracking-tight", className)}
>
	{@render children?.()}
</Dialog.Title>
