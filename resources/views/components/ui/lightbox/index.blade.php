<div x-data="{
    lightboxOpen: false,
    imageUrl: '',
    downloadUrl: '',
    fileName: '',
    openLightbox(url, download, name) {
        this.imageUrl = url;
        this.downloadUrl = download;
        this.fileName = name;
        this.lightboxOpen = true;
        document.body.style.overflow = 'hidden';
    },
    closeLightbox() {
        this.lightboxOpen = false;
        document.body.style.overflow = '';
    }
}" {{ $attributes }}>
    {{ $slot }}

    <template x-teleport="body">
        <div x-show="lightboxOpen" x-cloak class="fixed inset-0 z-50 flex flex-col bg-neutral-950/95 p-4 text-white md:p-8" role="dialog" aria-modal="true" aria-label="Visualização do comprovante" @keydown.escape.window="closeLightbox">
            <div class="mb-4 flex shrink-0 items-center justify-between gap-4">
                <p class="truncate text-sm font-medium" x-text="fileName"></p>
                <div class="flex shrink-0 items-center gap-2">
                    <a :href="downloadUrl" class="rounded-full p-2.5 transition hover:bg-white/15 focus-visible:outline-2 focus-visible:outline-white" aria-label="Baixar imagem original">
                        <x-heroicon-o-arrow-down-tray class="size-6" />
                    </a>
                    <button type="button" @click="closeLightbox" class="cursor-pointer rounded-full p-2.5 transition hover:bg-white/15 focus-visible:outline-2 focus-visible:outline-white" aria-label="Fechar imagem">
                        <x-heroicon-o-x-mark class="size-6" />
                    </button>
                </div>
            </div>
            <div class="flex min-h-0 flex-1 items-center justify-center" @click.self="closeLightbox">
                <img :src="imageUrl" :alt="fileName" class="max-h-full max-w-full rounded-md object-contain shadow-2xl" />
            </div>
        </div>
    </template>
</div>
