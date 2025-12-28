<script>
    import { Link, page } from "@inertiajs/svelte";
    import {
        Home,
        Users,
        Calendar,
        Kanban,
        Menu,
        User,
        LogOut,
    } from "lucide-svelte";

    // Props - receive children snippet from parent
    let { children } = $props();

    // State for mobile menu
    let mobileMenuOpen = $state(false);

    // Get authenticated user from Inertia page props
    let user = $derived($page.props.auth.user);

    // Get user initials for avatar
    function getUserInitials(name) {
        if (!name) return "U";
        return name
            .split(" ")
            .map((n) => n[0])
            .join("")
            .toUpperCase()
            .slice(0, 2);
    }

    // Navigation items
    const navItems = [
        { href: "/admin/dashboard", label: "Dashboard", icon: Home },
        { href: "/admin/members", label: "Soci", icon: Users },
        { href: "/admin/events", label: "Eventi", icon: Calendar },
        { href: "/admin/projects", label: "Progetti", icon: Kanban },
    ];
    // State for user menu
    let userMenuOpen = $state(false);
</script>

<div class="min-h-screen bg-background">
    <!-- Desktop Sidebar -->
    <aside
        class="fixed left-0 top-0 z-40 h-screen w-60 border-r border-border bg-card hidden lg:block"
    >
        <div class="flex h-full flex-col">
            <!-- Logo/Brand -->
            <div
                class="flex h-16 items-center gap-3 border-b border-border px-6"
            >
                <img
                    src="/logo.png"
                    alt="Pro Loco"
                    class="h-8 w-8 object-contain"
                />
                <h1 class="text-lg font-semibold text-foreground">
                    Pro Loco Admin
                </h1>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 space-y-1 px-3 py-4">
                {#each navItems as item}
                    <Link
                        href={item.href}
                        class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
                    >
                        <item.icon class="h-5 w-5" />
                        <span>{item.label}</span>
                    </Link>
                {/each}
            </nav>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="lg:pl-60">
        <!-- Top Bar -->
        <header
            class="sticky top-0 z-30 flex h-16 items-center gap-4 border-b border-border bg-background px-4 sm:px-6"
        >
            <!-- Mobile Menu Trigger -->
            <button
                onclick={() => (mobileMenuOpen = !mobileMenuOpen)}
                class="lg:hidden inline-flex items-center justify-center rounded-md p-2 text-muted-foreground hover:bg-accent hover:text-accent-foreground"
                aria-label="Toggle menu"
            >
                <Menu class="h-6 w-6" />
            </button>

            <!-- Spacer -->
            <div class="flex-1"></div>

            <!-- User Dropdown -->
            <div class="relative">
                <button
                    onclick={() => (userMenuOpen = !userMenuOpen)}
                    class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-foreground hover:bg-accent"
                >
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-primary text-primary-foreground text-xs font-semibold"
                    >
                        {getUserInitials(user?.name)}
                    </div>
                    <span class="hidden sm:inline"
                        >{user?.name || "Utente"}</span
                    >
                </button>

                <!-- Dropdown Menu -->
                {#if userMenuOpen}
                    <!-- Backdrop to close on click outside -->
                    <button
                        class="fixed inset-0 z-40 cursor-default w-full h-full border-0 bg-transparent"
                        onclick={() => (userMenuOpen = false)}
                        onkeydown={(e) =>
                            e.key === "Escape" && (userMenuOpen = false)}
                        aria-label="Close user menu"
                        type="button"
                    ></button>

                    <div
                        class="absolute right-0 mt-2 w-48 rounded-md border border-border bg-popover shadow-lg z-50"
                    >
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-popover-foreground hover:bg-accent"
                        >
                            <LogOut class="h-4 w-4" />
                            <span>Logout</span>
                        </Link>
                    </div>
                {/if}
            </div>
        </header>

        <!-- Page Content -->
        <main class="p-4 sm:p-6 lg:p-8">
            {@render children()}
        </main>
    </div>

    <!-- Mobile Sidebar (Sheet) -->
    {#if mobileMenuOpen}
        <!-- Backdrop -->
        <div
            class="fixed inset-0 z-50 bg-background/80 backdrop-blur-sm lg:hidden"
            onclick={() => (mobileMenuOpen = false)}
            onkeydown={(e) => e.key === "Escape" && (mobileMenuOpen = false)}
            role="button"
            tabindex="-1"
            aria-label="Close menu"
        ></div>

        <!-- Mobile Menu -->
        <aside
            class="fixed left-0 top-0 z-50 h-screen w-72 border-r border-border bg-card lg:hidden"
        >
            <div class="flex h-full flex-col">
                <!-- Logo/Brand -->
                <div
                    class="flex h-16 items-center justify-between border-b border-border px-6"
                >
                    <div class="flex items-center gap-3">
                        <img
                            src="/logo.png"
                            alt="Pro Loco"
                            class="h-8 w-8 object-contain"
                        />
                        <h1 class="text-lg font-semibold text-foreground">
                            Pro Loco Admin
                        </h1>
                    </div>
                    <button
                        onclick={() => (mobileMenuOpen = false)}
                        class="rounded-md p-2 text-muted-foreground hover:bg-accent hover:text-accent-foreground"
                        aria-label="Close menu"
                    >
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 space-y-1 px-3 py-4">
                    {#each navItems as item}
                        <Link
                            href={item.href}
                            onclick={() => (mobileMenuOpen = false)}
                            class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
                        >
                            <item.icon class="h-5 w-5" />
                            <span>{item.label}</span>
                        </Link>
                    {/each}
                </nav>
            </div>
        </aside>
    {/if}
</div>
