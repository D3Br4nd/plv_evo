<script>
    import { useForm } from "@inertiajs/svelte";
    import { Lock } from "lucide-svelte";
    import PublicLayout from "@/layouts/PublicLayout.svelte";
    import { Button } from "@/lib/components/ui/button";
    import { Input } from "@/lib/components/ui/input";
    import * as Card from "@/lib/components/ui/card";

    let { token, email } = $props();

    // Form state - initialize with current prop values
    const form = useForm({
        token: token,
        email: email || "",
        password: "",
        password_confirmation: "",
    });

    function handleSubmit(e) {
        e.preventDefault();
        $form.post("/reset-password");
    }
</script>

<PublicLayout title="Reimposta Password">
    <div class="mx-auto w-full max-w-md">
        <Card.Root>
            <Card.Header class="text-center">
                <Card.Title>Reimposta Password</Card.Title>
                <Card.Description>
                    Inserisci la tua nuova password
                </Card.Description>
            </Card.Header>
            <Card.Content>
                <form onsubmit={handleSubmit} class="space-y-4">
                    <div class="space-y-1.5">
                        <label for="email" class="text-sm font-medium"
                            >Email</label
                        >
                        <Input
                            id="email"
                            type="email"
                            bind:value={$form.email}
                            required
                            readonly
                            class="bg-muted"
                        />
                        {#if $form.errors.email}
                            <p class="text-sm text-destructive">
                                {$form.errors.email}
                            </p>
                        {/if}
                    </div>

                    <div class="space-y-1.5">
                        <label for="password" class="text-sm font-medium"
                            >Nuova Password</label
                        >
                        <div class="relative">
                            <Lock
                                class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <Input
                                id="password"
                                type="password"
                                bind:value={$form.password}
                                required
                                placeholder="••••••••"
                                class="pl-9"
                            />
                        </div>
                        {#if $form.errors.password}
                            <p class="text-sm text-destructive">
                                {$form.errors.password}
                            </p>
                        {/if}
                    </div>

                    <div class="space-y-1.5">
                        <label
                            for="password_confirmation"
                            class="text-sm font-medium"
                        >
                            Conferma Password
                        </label>
                        <div class="relative">
                            <Lock
                                class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <Input
                                id="password_confirmation"
                                type="password"
                                bind:value={$form.password_confirmation}
                                required
                                placeholder="••••••••"
                                class="pl-9"
                            />
                        </div>
                    </div>

                    <Button
                        type="submit"
                        disabled={$form.processing}
                        class="w-full"
                    >
                        {#if $form.processing}
                            Reimpostazione in corso...
                        {:else}
                            Reimposta Password
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
