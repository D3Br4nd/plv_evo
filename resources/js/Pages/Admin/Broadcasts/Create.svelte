<script>
    import AdminLayout from "@/layouts/AdminLayout.svelte";
    import { Button } from "@/lib/components/ui/button";
    import * as Card from "@/lib/components/ui/card";
    import * as Dialog from "@/lib/components/ui/dialog";
    import { Input } from "@/lib/components/ui/input";
    import { Label } from "@/lib/components/ui/label";
    import { router } from "@inertiajs/svelte";
    import { toast } from "svelte-sonner";
    import { onMount, onDestroy } from "svelte";
    import { Editor } from "@tiptap/core";
    import StarterKit from "@tiptap/starter-kit";
    import Link from "@tiptap/extension-link";
    import BoldIcon from "@tabler/icons-svelte/icons/bold";
    import ItalicIcon from "@tabler/icons-svelte/icons/italic";
    import ListIcon from "@tabler/icons-svelte/icons/list";
    import ListNumbersIcon from "@tabler/icons-svelte/icons/list-numbers";
    import LinkIcon from "@tabler/icons-svelte/icons/link";
    import SendIcon from "@tabler/icons-svelte/icons/send";
    import UploadIcon from "@tabler/icons-svelte/icons/upload";
    import XIcon from "@tabler/icons-svelte/icons/x";
    import UsersIcon from "@tabler/icons-svelte/icons/users";

    let { activeMembersCount } = $props();

    let title = $state("");
    let editorElement = $state(null);
    let editor = $state(null);
    let featuredImageFile = $state(null);
    let featuredImagePreview = $state(null);
    let attachmentFile = $state(null);
    let imageInputRef = $state(null);
    let attachmentInputRef = $state(null);
    let submitting = $state(false);

    // Link dialog state
    let linkDialogOpen = $state(false);
    let linkUrl = $state("");

    onMount(() => {
        editor = new Editor({
            element: editorElement,
            extensions: [
                StarterKit,
                Link.configure({
                    openOnClick: false,
                    HTMLAttributes: {
                        class: "text-primary underline",
                    },
                }),
            ],
            content: "",
            editorProps: {
                attributes: {
                    class: "prose prose-sm max-w-none min-h-[200px] p-4 focus:outline-none",
                },
            },
            onTransaction: () => {
                // Force reactivity
                editor = editor;
            },
        });
    });

    onDestroy(() => {
        if (editor) {
            editor.destroy();
        }
    });

    function handleImageSelect(e) {
        const file = e.target.files[0];
        if (!file) return;

        featuredImageFile = file;
        featuredImagePreview = URL.createObjectURL(file);
    }

    function removeImage() {
        featuredImageFile = null;
        if (featuredImagePreview) {
            URL.revokeObjectURL(featuredImagePreview);
            featuredImagePreview = null;
        }
        if (imageInputRef) imageInputRef.value = "";
    }

    function handleAttachmentSelect(e) {
        const file = e.target.files[0];
        if (!file) return;
        attachmentFile = file;
    }

    function removeAttachment() {
        attachmentFile = null;
        if (attachmentInputRef) attachmentInputRef.value = "";
    }

    function openLinkDialog() {
        const previousUrl = editor?.getAttributes("link").href || "";
        linkUrl = previousUrl;
        linkDialogOpen = true;
    }

    function applyLink() {
        if (linkUrl === "") {
            editor?.chain().focus().extendMarkRange("link").unsetLink().run();
        } else {
            editor
                ?.chain()
                .focus()
                .extendMarkRange("link")
                .setLink({ href: linkUrl })
                .run();
        }
        linkDialogOpen = false;
        linkUrl = "";
    }

    function removeLink() {
        editor?.chain().focus().extendMarkRange("link").unsetLink().run();
        linkDialogOpen = false;
        linkUrl = "";
    }

    function handleSubmit() {
        if (!title.trim()) {
            toast.error("Inserisci un titolo.");
            return;
        }

        const content = editor?.getHTML();
        if (!content || content === "<p></p>") {
            toast.error("Inserisci un contenuto.");
            return;
        }

        submitting = true;

        const formData = new FormData();
        formData.append("title", title);
        formData.append("content", content);
        if (featuredImageFile) {
            formData.append("featured_image", featuredImageFile);
        }
        if (attachmentFile) {
            formData.append("attachment", attachmentFile);
        }

        router.post("/admin/broadcasts", formData, {
            onSuccess: () => {
                submitting = false;
            },
            onError: (errors) => {
                submitting = false;
                const firstError = Object.values(errors)[0];
                if (firstError) toast.error(firstError);
            },
        });
    }
</script>

<AdminLayout title="Nuova Notifica Broadcast">
    <div class="space-y-6">
        <div>
            <h1 class="text-3xl font-bold tracking-tight">
                Nuova Notifica Broadcast
            </h1>
            <p class="mt-1 text-muted-foreground">
                Questa notifica verrà inviata a tutti i soci attivi
            </p>
        </div>

        <div
            class="flex items-center gap-2 rounded-lg border border-primary/20 bg-primary/5 p-4"
        >
            <UsersIcon class="size-5 text-primary" />
            <span class="text-sm font-medium">
                {activeMembersCount} soci attivi riceveranno questa notifica
            </span>
        </div>

        <Card.Root>
            <Card.Content class="space-y-6 p-6">
                <!-- Title -->
                <div class="space-y-2">
                    <Label for="title"
                        >Titolo <span class="text-red-500">*</span></Label
                    >
                    <Input
                        id="title"
                        type="text"
                        bind:value={title}
                        placeholder="Titolo della notifica..."
                    />
                </div>

                <!-- Rich Text Editor -->
                <div class="space-y-2">
                    <Label>Contenuto <span class="text-red-500">*</span></Label>
                    <div class="rounded-lg border">
                        <!-- Toolbar -->
                        <div
                            class="flex flex-wrap gap-1 border-b bg-muted/30 p-2"
                        >
                            <Button
                                type="button"
                                variant={editor?.isActive("bold")
                                    ? "secondary"
                                    : "ghost"}
                                size="icon"
                                class="size-8"
                                onclick={() =>
                                    editor?.chain().focus().toggleBold().run()}
                                disabled={!editor}
                            >
                                <BoldIcon class="size-4" />
                            </Button>
                            <Button
                                type="button"
                                variant={editor?.isActive("italic")
                                    ? "secondary"
                                    : "ghost"}
                                size="icon"
                                class="size-8"
                                onclick={() =>
                                    editor
                                        ?.chain()
                                        .focus()
                                        .toggleItalic()
                                        .run()}
                                disabled={!editor}
                            >
                                <ItalicIcon class="size-4" />
                            </Button>
                            <div class="mx-1 w-px bg-border"></div>
                            <Button
                                type="button"
                                variant={editor?.isActive("bulletList")
                                    ? "secondary"
                                    : "ghost"}
                                size="icon"
                                class="size-8"
                                onclick={() =>
                                    editor
                                        ?.chain()
                                        .focus()
                                        .toggleBulletList()
                                        .run()}
                                disabled={!editor}
                            >
                                <ListIcon class="size-4" />
                            </Button>
                            <Button
                                type="button"
                                variant={editor?.isActive("orderedList")
                                    ? "secondary"
                                    : "ghost"}
                                size="icon"
                                class="size-8"
                                onclick={() =>
                                    editor
                                        ?.chain()
                                        .focus()
                                        .toggleOrderedList()
                                        .run()}
                                disabled={!editor}
                            >
                                <ListNumbersIcon class="size-4" />
                            </Button>
                            <div class="mx-1 w-px bg-border"></div>
                            <Button
                                type="button"
                                variant={editor?.isActive("link")
                                    ? "secondary"
                                    : "ghost"}
                                size="icon"
                                class="size-8"
                                onclick={openLinkDialog}
                                disabled={!editor}
                            >
                                <LinkIcon class="size-4" />
                            </Button>
                        </div>
                        <!-- Editor -->
                        <div bind:this={editorElement}></div>
                    </div>
                </div>

                <!-- Featured Image -->
                <div class="space-y-2">
                    <Label>Immagine in evidenza (opzionale)</Label>
                    {#if featuredImagePreview}
                        <div class="relative inline-block">
                            <img
                                src={featuredImagePreview}
                                alt="Preview"
                                class="max-h-48 rounded-lg border object-cover"
                            />
                            <Button
                                type="button"
                                variant="destructive"
                                size="icon"
                                class="absolute -right-2 -top-2 size-6 rounded-full"
                                onclick={removeImage}
                            >
                                <XIcon class="size-3" />
                            </Button>
                        </div>
                    {:else}
                        <div>
                            <Button
                                type="button"
                                variant="outline"
                                onclick={() => imageInputRef?.click()}
                            >
                                <UploadIcon class="mr-2 size-4" />
                                Carica immagine
                            </Button>
                            <input
                                type="file"
                                accept="image/*"
                                class="hidden"
                                bind:this={imageInputRef}
                                onchange={handleImageSelect}
                            />
                        </div>
                    {/if}
                </div>

                <!-- Attachment -->
                <div class="space-y-2">
                    <Label>Allegato PDF/immagine (opzionale)</Label>
                    {#if attachmentFile}
                        <div
                            class="flex items-center gap-2 rounded-lg border bg-muted/30 p-3"
                        >
                            <span class="flex-1 truncate text-sm">
                                {attachmentFile.name}
                            </span>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                class="size-6"
                                onclick={removeAttachment}
                            >
                                <XIcon class="size-4" />
                            </Button>
                        </div>
                    {:else}
                        <div>
                            <Button
                                type="button"
                                variant="outline"
                                onclick={() => attachmentInputRef?.click()}
                            >
                                <UploadIcon class="mr-2 size-4" />
                                Carica allegato
                            </Button>
                            <input
                                type="file"
                                accept=".pdf,.jpg,.jpeg,.png"
                                class="hidden"
                                bind:this={attachmentInputRef}
                                onchange={handleAttachmentSelect}
                            />
                            <p class="mt-1 text-xs text-muted-foreground">
                                Formati supportati: PDF, JPG, PNG (max 10MB)
                            </p>
                        </div>
                    {/if}
                </div>

                <!-- Submit -->
                <div class="flex justify-end gap-3 border-t pt-6">
                    <Button variant="outline" href="/admin/broadcasts">
                        Annulla
                    </Button>
                    <Button onclick={handleSubmit} disabled={submitting}>
                        <SendIcon class="mr-2 size-4" />
                        {submitting
                            ? "Invio in corso..."
                            : `Invia a ${activeMembersCount} soci`}
                    </Button>
                </div>
            </Card.Content>
        </Card.Root>
    </div>

    <!-- Link Dialog -->
    <Dialog.Root bind:open={linkDialogOpen}>
        <Dialog.Content class="sm:max-w-md">
            <Dialog.Header>
                <Dialog.Title>Inserisci Link</Dialog.Title>
                <Dialog.Description>
                    Inserisci l'URL del link da applicare al testo selezionato.
                </Dialog.Description>
            </Dialog.Header>
            <div class="space-y-4 py-4">
                <div class="space-y-2">
                    <Label for="linkUrl">URL</Label>
                    <Input
                        id="linkUrl"
                        type="url"
                        bind:value={linkUrl}
                        placeholder="https://esempio.com"
                    />
                </div>
            </div>
            <Dialog.Footer class="flex gap-2">
                {#if editor?.isActive("link")}
                    <Button variant="destructive" onclick={removeLink}>
                        Rimuovi Link
                    </Button>
                {/if}
                <Button
                    variant="outline"
                    onclick={() => (linkDialogOpen = false)}
                >
                    Annulla
                </Button>
                <Button onclick={applyLink}>Applica</Button>
            </Dialog.Footer>
        </Dialog.Content>
    </Dialog.Root>
</AdminLayout>

<style>
    :global(.ProseMirror) {
        min-height: 200px;
    }
    :global(.ProseMirror p.is-editor-empty:first-child::before) {
        color: #adb5bd;
        content: attr(data-placeholder);
        float: left;
        height: 0;
        pointer-events: none;
    }
    :global(.ProseMirror ul) {
        list-style-type: disc;
        padding-left: 1.5rem;
    }
    :global(.ProseMirror ol) {
        list-style-type: decimal;
        padding-left: 1.5rem;
    }
    :global(.ProseMirror a) {
        color: hsl(var(--primary));
        text-decoration: underline;
    }
</style>
