<script>
    import { useForm, page } from "@inertiajs/svelte";
    import { Mail } from "lucide-svelte";
    import PublicLayout from "@/layouts/PublicLayout.svelte";
    import { Button } from "@/lib/components/ui/button";
    import { Input } from "@/lib/components/ui/input";
    import * as Card from "@/lib/components/ui/card";

    // Form state
    const form = useForm({
        email: "",
    });

    let status = $derived($page.props.status);

    function handleSubmit(e) {
        e.preventDefault();
        $form.post("/forgot-password");
    }
</script>

<PublicLayout title="Recupera Password">
    <div class="mx-auto w-full max-w-md">
        <Card.Root>
            <Card.Header class="text-center">
                <Card.Title>Password Dimenticata?</Card.Title>
                <Card.Description>
                    Inserisci la tua email e ti invieremo un link per
                    reimpostare la password
                </Card.Description>
            </Card.Header>
            <Card.Content>
                {#if status}
                    <div
                        class="mb-4 rounded-lg bg-primary/10 p-3 text-sm text-primary"
                    >
                        {status}
                    </div>
                {/if}

                <form onsubmit={handleSubmit} class="space-y-4">
                    <div class="space-y-1.5">
                        <label for="email" class="text-sm font-medium"
                            >Email</label
                        >
                        <div class="relative">
                            <Mail
                                class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <Input
                                id="email"
                                type="email"
                                bind:value={$form.email}
                                required
                                placeholder="tua-email@example.com"
                                class="pl-9"
                            />
                        </div>
                        {#if $form.errors.email}
                            <p class="text-sm text-destructive">
                                {$form.errors.email}
                            </p>
                        {/if}
                    </div>

                    <Button
                        type="submit"
                        disabled={$form.processing}
                        class="w-full"
                    >
                        {#if $form.processing}
                            Invio in corso...
                        {:else}
                            Invia Link di Reset
                        {/if}
                    </Button>
                </form>
            </Card.Content>
            <Card.Footer class="justify-center">
                <a href="/login" class="text-sm text-primary hover:underline">
                    Torna al login
                </a>
            </Card.Footer>
        </Card.Root>
    </div>
</PublicLayout>
