@php
    $statePath = $getStatePath();
    $record = $getRecord();
    $isReorderable = $isReorderable();
    $isDeletable = $isDeletable();
    $isMultiselect = $isMultiselect();

    $existingAttachments = $record?->attachments?->sortBy('sort_order')?->values() ?? collect();
    $attachmentsJson = $existingAttachments->map(fn ($a) => [
        'id' => $a->id,
        'path' => $a->path,
        'url' => $a->url,
        'name' => $a->original_name,
    ])->values()->toJson();

    $originalNamesPath = preg_replace('/attachments$/', 'attachment_original_names', $statePath);
    $uid = 'att-' . str_replace('.', '-', $statePath);
    $uploadUrl = route('filament.admin.property.upload-attachment');
@endphp

<script type="application/json" id="{{ $uid }}-data">{!! $attachmentsJson !!}</script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" />

<style>
    [data-attach-card] { cursor: grab; }
    [data-attach-card]:active { cursor: grabbing; }
    [data-attach-card]:hover {
        border-color: var(--pdc-primary) !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.10), 0 1px 3px rgba(0,0,0,0.08) !important;
        transform: translateY(-1px);
    }
    [data-attach-card].pdc-dragging {
        opacity: 0.5 !important;
        transform: scale(0.97);
    }
    [data-attach-card].pdc-drag-over {
        outline: 2px dashed var(--pdc-primary) !important;
        outline-offset: 2px;
        box-shadow: 0 0 0 4px rgba(40,88,84,0.15) !important;
    }
    .pdc-editor-modal {
        position: fixed;
        inset: 0;
        z-index: 2147483647;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(0,0,0,0.85);
        padding: 1rem;
    }
    .pdc-editor-panel {
        background: #0b0f14;
        border: 1px solid rgba(255,255,255,0.12);
        border-radius: 0.75rem;
        width: 100%;
        max-width: 960px;
        max-height: 92vh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(0,0,0,0.5);
    }
    .pdc-editor-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid rgba(255,255,255,0.08);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }
    .pdc-editor-title {
        color: white;
        font-weight: 600;
        font-size: 0.95rem;
    }
    .pdc-editor-body {
        flex: 1;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #0b0f14;
        min-height: 320px;
        max-height: 60vh;
        position: relative;
    }
    .pdc-editor-body img {
        max-width: 100%;
        max-height: 60vh;
        display: block;
    }
    .pdc-editor-footer {
        padding: 1rem 1.25rem;
        border-top: 1px solid rgba(255,255,255,0.08);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        flex-wrap: wrap;
        background: #0b0f14;
    }
    .pdc-editor-controls {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    .pdc-editor-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        padding: 0.5rem 0.85rem;
        border-radius: 0.5rem;
        font-size: 0.8rem;
        font-weight: 600;
        border: 1px solid transparent;
        cursor: pointer;
        transition: all 0.15s;
    }
    .pdc-editor-btn-primary {
        background: var(--pdc-primary);
        color: white;
        border-color: var(--pdc-primary);
    }
    .pdc-editor-btn-primary:hover {
        background: var(--pdc-primary-darker);
        border-color: var(--pdc-primary-darker);
    }
    .pdc-editor-btn-secondary {
        background: #1f2937;
        color: white;
        border-color: rgba(255,255,255,0.15);
    }
    .pdc-editor-btn-secondary:hover {
        background: #111827;
        border-color: rgba(255,255,255,0.25);
    }
    .pdc-editor-btn-ghost {
        background: rgba(255,255,255,0.08);
        color: white;
        border-color: rgba(255,255,255,0.12);
    }
    .pdc-editor-btn-ghost:hover {
        background: rgba(255,255,255,0.14);
    }
    .pdc-editor-btn-ghost.active {
        background: var(--pdc-primary);
        border-color: var(--pdc-primary);
        color: white;
    }
    .cropper-container {
        z-index: 100001 !important;
    }
    .cropper-modal {
        background: rgba(0,0,0,0.5) !important;
    }
</style>

<div
    x-data="{
        files: [],
        selected: [],
        uploadUrl: null,
        csrfToken: null,
        draggedIndex: null,
        lightboxOpen: false,
        lightboxIndex: 0,
        editorOpen: false,
        editorIndex: null,
        editorFile: null,
        cropper: null,
        editorAspectRatio: null,
        scaleX: 1,
        scaleY: 1,
        init() {
            try { this.files = JSON.parse(document.getElementById('{{ $uid }}-data').textContent) || []; } catch(e){ this.files=[]; }
            this.uploadUrl = this.$el.dataset.uploadUrl;
            this.csrfToken = document.querySelector('meta[name=&quot;csrf-token&quot;]')?.content || document.querySelector('meta[name=csrf-token]')?.content;
            window.__pdcAttachments = this;
            // Load cropper.js if not already loaded
            if (!window.Cropper && !document.querySelector('script[data-cropper]')) {
                const s = document.createElement('script');
                s.src = 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js';
                s.setAttribute('data-cropper','1');
                document.head.appendChild(s);
            }
        },
        get hasSelection() { return this.selected.length > 0 },
        get allSelected() { return this.files.length > 0 && this.selected.length === this.files.length },
        get paths() { return this.files.map(f => f.path) },
        get names() {
            const map = {};
            this.files.forEach(f => { map[f.path] = f.name; });
            return map;
        },
        sync() {
            $wire.set('{{ $statePath }}', this.paths, false);
            $wire.set('{{ $originalNamesPath }}', this.names, false);
            this.$nextTick(() => { $wire.$refresh && $wire.$refresh(); });
        },
        toggleSelect(id) {
            const idx = this.selected.indexOf(id);
            if (idx === -1) { this.selected.push(id); }
            else { this.selected.splice(idx, 1); }
        },
        selectAll() {
            this.selected = this.files.map(f => f.id);
        },
        clearSelection() { this.selected = []; },
        deleteSelected() {
            if (!this.selected.length) return;
            if (!confirm(`Dzēst ${this.selected.length} atlasītos failus?`)) return;
            this.files = this.files.filter(f => !this.selected.includes(f.id));
            this.selected = [];
            this.sync();
            $wire.$refresh && $wire.$refresh();
        },
        removeFile(id) {
            if (!confirm('Dzēst šo failu?')) return;
            this.files = this.files.filter(f => f.id !== id);
            this.selected = this.selected.filter(s => s !== id);
            this.sync();
        },
        onDragStart(e, index) {
            this.draggedIndex = index;
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', index);
            e.currentTarget.classList.add('pdc-dragging');
        },
        onDragOver(e, index) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            e.currentTarget.classList.add('pdc-drag-over');
        },
        onDragLeave(e) {
            e.currentTarget.classList.remove('pdc-drag-over');
        },
        onDrop(e, index) {
            e.preventDefault();
            e.currentTarget.classList.remove('pdc-drag-over');
            const from = this.draggedIndex;
            if (from === null || from === index) { this.draggedIndex = null; return; }
            const item = this.files.splice(from, 1)[0];
            const to = from < index ? index - 1 : index;
            this.files.splice(to, 0, item);
            this.draggedIndex = null;
            this.sync();
        },
        onDragEnd(e) {
            e.currentTarget.classList.remove('pdc-dragging');
            this.draggedIndex = null;
            this.$el.querySelectorAll('[data-attach-card]').forEach(el => el.classList.remove('pdc-drag-over','pdc-dragging'));
        },
        openLightbox(index) {
            if (this.editorOpen) return;
            this.lightboxIndex = index;
            this.lightboxOpen = true;
            document.body.style.overflow = 'hidden';
        },
        closeLightbox() {
            this.lightboxOpen = false;
            document.body.style.overflow = '';
        },
        lightboxPrev() {
            this.lightboxIndex = this.lightboxIndex > 0 ? this.lightboxIndex - 1 : this.files.length - 1;
        },
        lightboxNext() {
            this.lightboxIndex = this.lightboxIndex < this.files.length - 1 ? this.lightboxIndex + 1 : 0;
        },
        get lightboxFile() { return this.files[this.lightboxIndex] || null; },
        openEditor(index) {
            const file = this.files[index];
            if (!file || !file.url.match(/\.(jpe?g|png|webp|gif|bmp)$/i) && !file.name.match(/\.(jpe?g|png|webp|gif|bmp)$/i)) {
                alert('Rediģēt var tikai attēlus (jpg, png, webp).');
                return;
            }
            this.editorIndex = index;
            this.editorFile = file;
            this.editorOpen = true;
            document.body.style.overflow = 'hidden';
            this.$nextTick(() => this.initCropper());
        },
        closeEditor() {
            this.editorOpen = false;
            document.body.style.overflow = '';
            this.destroyCropper();
            this.editorFile = null;
            this.editorIndex = null;
        },
        initCropper() {
            this.destroyCropper();
            const img = this.$refs.editorImage;
            if (!img) return;
            // Wait for Cropper to be available
            const tryInit = () => {
                if (!window.Cropper) {
                    setTimeout(tryInit, 200);
                    return;
                }
                img.src = this.editorFile.url;
                img.onload = () => {
                    this.cropper = new window.Cropper(img, {
                        viewMode: 1,
                        autoCropArea: 1,
                        responsive: true,
                        background: false,
                        guides: true,
                        center: true,
                        highlight: true,
                        cropBoxMovable: true,
                        cropBoxResizable: true,
                        toggleDragModeOnDblclick: false,
                    });
                };
                // If already cached
                if (img.complete) {
                    setTimeout(() => {
                        if (!this.cropper) {
                            this.cropper = new window.Cropper(img, {
                                viewMode: 1,
                                autoCropArea: 1,
                                responsive: true,
                                background: false,
                            });
                        }
                    }, 100);
                }
            };
            tryInit();
        },
        destroyCropper() {
            if (this.cropper && this.cropper.destroy) {
                try { this.cropper.destroy(); } catch(e) {}
                this.cropper = null;
            }
            this.scaleX = 1;
            this.scaleY = 1;
        },
        setAspectRatio(ratio) {
            this.editorAspectRatio = ratio;
            if (!this.cropper) return;
            this.cropper.setAspectRatio(ratio);
        },
        rotate(deg) {
            if (!this.cropper) return;
            this.cropper.rotate(deg);
        },
        flipHorizontal() {
            if (!this.cropper) return;
            this.scaleX *= -1;
            this.cropper.scaleX(this.scaleX);
        },
        flipVertical() {
            if (!this.cropper) return;
            this.scaleY *= -1;
            this.cropper.scaleY(this.scaleY);
        },
        resetCropper() {
            if (!this.cropper) return;
            this.cropper.reset();
            this.scaleX = 1;
            this.scaleY = 1;
            this.cropper.scaleX(this.scaleX);
            this.cropper.scaleY(this.scaleY);
        },
        async saveEditor() {
            if (!this.cropper || this.editorIndex === null) return;
            const canvas = this.cropper.getCroppedCanvas({
                maxWidth: 2400,
                maxHeight: 2400,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high',
                fillColor: '#fff',
            });
            if (!canvas) {
                alert('Neizdevās apgriezt attēlu.');
                return;
            }
            canvas.toBlob(async (blob) => {
                if (!blob) {
                    alert('Neizdevās saglabāt attēlu.');
                    return;
                }
                const ext = (this.editorFile.name.split('.').pop() || 'jpg').toLowerCase();
                const fileName = this.editorFile.name.replace(/\.[^/.]+$/, '') + '-crop.' + ext;
                const file = new File([blob], fileName, { type: blob.type || 'image/jpeg' });
                const formData = new FormData();
                formData.append('file', file);
                formData.append('_token', this.csrfToken);
                try {
                    const resp = await fetch(this.uploadUrl, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': this.csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const result = await resp.json();
                    if (result.path) {
                        // Replace old file with new cropped one, keep same position
                        this.files[this.editorIndex] = {
                            id: this.editorFile.id,
                            path: result.path,
                            url: result.url,
                            name: result.name || fileName,
                        };
                        this.sync();
                        this.closeEditor();
                    } else {
                        alert('Augšupielāde neizdevās.');
                    }
                } catch (err) {
                    console.error('Crop upload failed:', err);
                    alert('Kļūda saglabājot.');
                }
            }, this.editorFile.name.match(/\.png$/i) ? 'image/png' : 'image/jpeg', 0.92);
        },
        async handleUpload(e) {
            const input = e.target || e;
            const newFiles = Array.from(input.files || []);
            if (!newFiles.length) return;
            for (const file of newFiles) {
                const formData = new FormData();
                formData.append('file', file);
                formData.append('_token', this.csrfToken);
                try {
                    const resp = await fetch(this.uploadUrl, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': this.csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const result = await resp.json();
                    if (result.path) {
                        this.files.push({
                            id: 'new-' + Date.now() + '-' + Math.random().toString(36).slice(2, 8),
                            path: result.path,
                            url: result.url,
                            name: result.name || file.name,
                        });
                        this.sync();
                    }
                } catch (err) {
                    console.error('Upload failed:', err);
                }
            }
            input.value = '';
        }
    }"
    wire:ignore.self
    data-upload-url="{{ $uploadUrl }}"
    x-on:keydown.escape.window="if(lightboxOpen) closeLightbox(); if(editorOpen) closeEditor()"
    x-on:keydown.arrow-left.window="if(lightboxOpen) lightboxPrev()"
    x-on:keydown.arrow-right.window="if(lightboxOpen) lightboxNext()"
>
    <input
        type="file"
        id="{{ $uid }}-upload"
        multiple
        accept="{{ implode(',', config('attachments.accepted_file_types', ['image/*'])) }}"
        class="hidden"
        x-on:change="handleUpload($event)"
    />
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
            @if($isMultiselect)
                <template x-if="files.length > 0">
                    <button
                        type="button"
                        x-on:click="allSelected ? clearSelection() : selectAll()"
                        class="fi-btn fi-size-sm fi-color fi-color-gray fi-outlined"
                        :class="allSelected ? 'fi-color-primary' : 'fi-color-gray'"
                    >
                        <svg style="width: 1rem; height: 1rem;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span x-text="allSelected ? 'Noņemt atlasi' : 'Atlasīt visus'"></span>
                    </button>
                </template>
                <template x-if="files.length > 0">
                    <label
                        for="{{ $uid }}-upload"
                        class="fi-btn fi-size-sm fi-color fi-color-primary"
                        style="cursor: pointer;"
                    >
                        <svg style="width: 1rem; height: 1rem;" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 3.75a.75.75 0 01.75.75v6.75h6.75a.75.75 0 010 1.5h-6.75v6.75a.75.75 0 01-1.5 0v-6.75H4.5a.75.75 0 010-1.5h6.75V4.5a.75.75 0 01.75-.75z" clip-rule="evenodd"/></svg>
                        <span>Pievienot failus</span>
                    </label>
                </template>
                <template x-if="hasSelection">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="font-size: 0.875rem; font-weight: 600; color: var(--pdc-primary);" x-text="selected.length + ' atlasīti'"></span>
                        <button
                            type="button"
                            x-on:click="deleteSelected()"
                            class="fi-btn fi-size-sm fi-color fi-color-danger fi-outlined"
                        >
                            <svg style="width: 1rem; height: 1rem;" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M16.5 4.478v.227a48.816 48.816 0 0 1 3.878.512.75.75 0 1 1-.256 1.478l-.209-.035-1.005 13.07a3 3 0 0 1-2.991 2.77H8.084a3 3 0 0 1-2.991-2.77L4.087 6.66l-.209.035a.75.75 0 0 1-.256-1.478A48.567 48.567 0 0 1 7.5 4.705v-.227c0-1.564 1.213-2.9 2.816-2.951a52.662 52.662 0 0 1 3.369 0c1.603.051 2.815 1.387 2.815 2.951Zm-6.136-1.452a51.196 51.196 0 0 1 3.273 0C14.39 3.05 15 3.684 15 4.478v.113a49.488 49.488 0 0 0-6 0v-.113c0-.794.609-1.428 1.364-1.452Zm-.355 5.945a.75.75 0 1 0-1.5.058l.347 9a.75.75 0 1 0 1.499-.058l-.346-9Zm5.48.058a.75.75 0 1 0-1.498-.058l-.347 9a.75.75 0 0 0 1.5.058l.345-9Z" clip-rule="evenodd"/></svg>
                            <span>Dzēst atlasītos</span>
                        </button>
                        <button
                            type="button"
                            x-on:click="clearSelection()"
                            class="fi-btn fi-size-sm fi-color fi-color-gray fi-outlined"
                        >
                            <span>Atcelt</span>
                        </button>
                    </div>
                </template>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
        <template x-for="(file, index) in files" :key="file.id">
            <div
                data-attach-card
                class="group"
                style="position: relative; border-radius: 0.75rem; overflow: hidden; aspect-ratio: 16/9; background: #f9fafb; transition: all 0.2s ease; cursor: grab; border: 1px solid #e5e7eb;"
                :style="{ border: selected.includes(file.id) ? '2px solid var(--pdc-primary)' : '1px solid #e5e7eb', boxShadow: selected.includes(file.id) ? '0 0 0 3px rgba(40,88,84,0.2)' : 'none' }"
                @if($isReorderable) draggable="true"
                x-on:dragstart="onDragStart($event, index)"
                x-on:dragover="onDragOver($event, index)"
                x-on:dragleave="onDragLeave($event)"
                x-on:drop="onDrop($event, index)"
                x-on:dragend="onDragEnd($event)"
                @endif
                x-on:click="if (! $event.target.closest('button')) openLightbox(index)"
                title="Velc, lai pārkārtotu • Klikšķini, lai apskatītu"
            >
                <img
                    :src="file.url"
                    :alt="file.name"
                    style="width: 100%; height: 100%; object-fit: cover; pointer-events: none;"
                    draggable="false"
                />

                @if($isMultiselect)
                    <button
                        type="button"
                        x-on:click.stop="toggleSelect(file.id)"
                        style="position: absolute; top: 0.5rem; left: 0.5rem; z-index: 10; height: 1.4rem; width: 1.4rem; border-radius: 0.35rem; border: 2px solid; display: flex; align-items: center; justify-content: center; transition: all 0.2s; box-shadow: 0 1px 4px rgba(0,0,0,0.35); cursor: pointer; background: rgba(255,255,255,0.96); border-color: #6b7280; color: transparent;"
                        :style="{ background: selected.includes(file.id) ? 'var(--pdc-primary)' : 'rgba(255,255,255,0.96)', borderColor: selected.includes(file.id) ? 'var(--pdc-primary)' : '#6b7280', color: selected.includes(file.id) ? 'white' : 'transparent' }"
                    >
                        <svg x-show="selected.includes(file.id)" style="width: 0.85rem; height: 0.85rem;" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </button>
                @endif

                <button
                    type="button"
                    x-on:click.stop="openEditor(index)"
                    style="position: absolute; top: 0.5rem; right: 2.7rem; z-index: 10; height: 1.6rem; width: 1.6rem; border-radius: 0.35rem; background: rgba(0,0,0,0.62); color: white; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,0.22); cursor: pointer; backdrop-filter: blur(2px);"
                    title="Rediģēt attēlu"
                >
                    <svg style="width: 0.85rem; height: 0.85rem;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.83 15.83a2 2 0 01-1.415.586H9a1 1 0 01-1-1v-1.415a2 2 0 01.586-1.414l9-9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 5l4 4"/></svg>
                </button>

                @if($isDeletable)
                    <button
                        type="button"
                        x-on:click.stop="removeFile(file.id)"
                        style="position: absolute; top: 0.5rem; right: 0.5rem; z-index: 10; height: 1.6rem; width: 1.6rem; border-radius: 9999px; background: rgba(0,0,0,0.55); color: white; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,0.3); cursor: pointer; backdrop-filter: blur(2px);"
                    >
                        <svg style="width: 0.9rem; height: 0.9rem;" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M5.47 5.47a.75.75 0 011.06 0L12 10.94l5.47-5.47a.75.75 0 111.06 1.06L13.06 12l5.47 5.47a.75.75 0 11-1.06 1.06L12 13.06l-5.47 5.47a.75.75 0 01-1.06-1.06L10.94 12 5.47 6.53a.75.75 0 010-1.06z" clip-rule="evenodd"/></svg>
                    </button>
                @endif

                <!-- Title on hover - accent container -->
                <div style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.55) 0%, transparent 45%); opacity: 0; display: flex; align-items: flex-end; padding: 0.6rem; transition: opacity 0.2s; pointer-events: none;" class="group-hover:opacity-100" x-bind:style="'opacity: ' + (selected.includes(file.id) ? '1' : '')">
                    <span style="background: var(--pdc-primary); color: white; font-size: 0.72rem; font-weight: 600; padding: 0.22rem 0.5rem; border-radius: 0.35rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: block; max-width: 100%; box-shadow: 0 1px 4px rgba(0,0,0,0.25);" x-text="file.name"></span>
                </div>

                @if($isReorderable)
                    <div class="opacity-60 group-hover:opacity-100 transition-opacity" style="position: absolute; top: 0.5rem; right: 4.6rem; z-index: 10; pointer-events: none;">
                        <div style="height: 1.6rem; width: 1.6rem; border-radius: 0.35rem; background: rgba(0,0,0,0.6); color: white; display: flex; align-items: center; justify-content: center; cursor: grab; border: 1px solid rgba(255,255,255,0.2);">
                            <svg style="width: 0.9rem; height: 0.9rem;" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M3 6.75A.75.75 0 013.75 6h16.5a.75.75 0 010 1.5H3.75A.75.75 0 013 6.75zM3 12a.75.75 0 01.75-.75h16.5a.75.75 0 010 1.5H3.75A.75.75 0 013 12zm0 5.25a.75.75 0 01.75-.75h16.5a.75.75 0 010 1.5H3.75a.75.75 0 01-.75-.75z" clip-rule="evenodd"/></svg>
                        </div>
                    </div>
                    <div x-show="index === 0" style="position: absolute; bottom: 0.5rem; left: 0.5rem; z-index: 10; background: var(--pdc-primary); color: white; font-size: 0.68rem; font-weight: 700; padding: 0.28rem 0.55rem; border-radius: 0.4rem; letter-spacing: 0.04em; box-shadow: 0 2px 8px rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.2); line-height: 1;">GALVENĀ</div>
                @endif
            </div>
        </template>
    </div>

    <div
        x-show="files.length === 0"
        style="text-align: center; padding: 2rem; border: 2px dashed #e5e7eb; border-radius: 0.75rem;"
    >
        <svg style="margin: 0 auto; height: 1.5rem; width: 1.5rem; color: #9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <p style="margin-top: 0.75rem; font-size: 0.875rem; color: #6b7280;">Nav pielikumu.</p>
        <label
            for="{{ $uid }}-upload"
            style="margin-top: 0.75rem; cursor: pointer;"
            class="fi-btn fi-size-sm fi-color fi-color-primary"
        >
            <svg style="width: 1rem; height: 1rem;" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 3.75a.75.75 0 01.75.75v6.75h6.75a.75.75 0 010 1.5h-6.75v6.75a.75.75 0 01-1.5 0v-6.75H4.5a.75.75 0 010-1.5h6.75V4.5a.75.75 0 01.75-.75z" clip-rule="evenodd"/></svg>
            <span>Pievienot failus</span>
        </label>
    </div>

    <!-- Editor Modal -->
    <template x-if="editorOpen">
        <div style="position: fixed; inset: 0; z-index: 2147483647; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.88); padding: 1rem;" x-transition.opacity x-on:click.self="closeEditor()">
            <div class="pdc-editor-panel" x-on:click.stop>
                <div class="pdc-editor-header">
                    <span class="pdc-editor-title" x-text="editorFile ? editorFile.name : 'Rediģēt attēlu'"></span>
                    <button type="button" x-on:click="closeEditor()" style="width: 2rem; height: 2rem; border-radius: 9999px; background: rgba(255,255,255,0.08); color: white; border: 1px solid rgba(255,255,255,0.12); display: inline-flex; align-items: center; justify-content: center; place-items:center; padding:0; line-height:0; cursor: pointer; box-sizing:border-box;">
                        <svg style="width: 1rem; height: 1rem; display:block; flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="pdc-editor-body">
                    <img x-ref="editorImage" style="max-width: 100%; max-height: 100%; display: block;" alt="Redaktora priekšējā pārskats" />
                </div>
                <div class="pdc-editor-footer">
                    <div class="pdc-editor-controls">
                        <button type="button" x-on:click="setAspectRatio(null)" :class="editorAspectRatio === null ? 'active' : ''" class="pdc-editor-btn pdc-editor-btn-ghost" title="Brīva forma">Brīvi</button>
                        <button type="button" x-on:click="setAspectRatio(16/9)" :class="editorAspectRatio === 16/9 ? 'active' : ''" class="pdc-editor-btn pdc-editor-btn-ghost">16:9</button>
                        <button type="button" x-on:click="setAspectRatio(4/3)" :class="editorAspectRatio === 4/3 ? 'active' : ''" class="pdc-editor-btn pdc-editor-btn-ghost">4:3</button>
                        <button type="button" x-on:click="setAspectRatio(1)" :class="editorAspectRatio === 1 ? 'active' : ''" class="pdc-editor-btn pdc-editor-btn-ghost">1:1</button>
                        <span style="width: 1px; height: 1.5rem; background: rgba(255,255,255,0.12); margin: 0 0.25rem;"></span>
                        <button type="button" x-on:click="rotate(-90)" class="pdc-editor-btn pdc-editor-btn-ghost" title="Pagriezt pa kreisi">
                            <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 016 6v3"/></svg>
                        </button>
                        <button type="button" x-on:click="rotate(90)" class="pdc-editor-btn pdc-editor-btn-ghost" title="Pagriezt pa labi">
                            <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 15l6-6m0 0l-6-6M21 9H9a6 6 0 00-6 6v3"/></svg>
                        </button>
                        <button type="button" x-on:click="flipHorizontal()" class="pdc-editor-btn pdc-editor-btn-ghost" title="Apvērst horizontāli">
                            <svg style="width: 1rem; height: 1rem;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="7" width="18" height="10" rx="1"/><path d="M12 7v10" stroke-dasharray="2 2"/><path d="M7 9l-2 3 2 3M17 15l2-3-2-3"/></svg>
                        </button>
                        <button type="button" x-on:click="flipVertical()" class="pdc-editor-btn pdc-editor-btn-ghost" title="Apvērst vertikāli">
                            <svg style="width: 1rem; height: 1rem;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="7" y="3" width="10" height="18" rx="1"/><path d="M7 12h10" stroke-dasharray="2 2"/><path d="M9 7l3-2 3 2M15 17l-3 2-3-2"/></svg>
                        </button>
                        <button type="button" x-on:click="resetCropper()" class="pdc-editor-btn pdc-editor-btn-ghost">Atiestatīt</button>
                    </div>
                    <div style="display: flex; gap: 0.5rem;">
                        <button type="button" x-on:click="closeEditor()" class="pdc-editor-btn pdc-editor-btn-secondary">Atcelt</button>
                        <button type="button" x-on:click="saveEditor()" class="pdc-editor-btn pdc-editor-btn-primary">Saglabāt</button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- Lightbox Gallery -->
    <template x-if="lightboxOpen">
        <div x-transition.opacity
            style="position: fixed; inset: 0; z-index: 2147483647; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.92); padding: 1rem;"
            x-on:click.self="closeLightbox()"
        >
        <button type="button" x-on:click="closeLightbox()" style="position: absolute; top: 1rem; right: 1rem; z-index: 10; width: 2.5rem; height: 2.5rem; border-radius: 9999px; background: rgba(255,255,255,0.12); color: white; border: 1px solid rgba(255,255,255,0.2); display:inline-flex; align-items:center; justify-content:center; place-items:center; padding:0; line-height:0; cursor:pointer; backdrop-filter: blur(4px); box-sizing:border-box;">
            <svg style="width: 1.25rem; height:1.25rem; display:block; flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <button type="button" x-show="files.length > 1" x-on:click.stop="lightboxPrev()" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); z-index:10; width: 2.75rem; height:2.75rem; border-radius: 9999px; background: rgba(255,255,255,0.12); color:white; border:1px solid rgba(255,255,255,0.2); display:inline-flex; align-items:center; justify-content:center; place-items:center; padding:0; line-height:0; cursor:pointer; backdrop-filter: blur(4px); box-sizing:border-box;">
            <svg style="width:1.4rem;height:1.4rem; display:block; flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <button type="button" x-show="files.length > 1" x-on:click.stop="lightboxNext()" style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); z-index:10; width: 2.75rem; height:2.75rem; border-radius: 9999px; background: rgba(255,255,255,0.12); color:white; border:1px solid rgba(255,255,255,0.2); display:inline-flex; align-items:center; justify-content:center; place-items:center; padding:0; line-height:0; cursor:pointer; backdrop-filter: blur(4px); box-sizing:border-box;">
            <svg style="width:1.4rem;height:1.4rem; display:block; flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
        <div style="max-width: 90vw; max-height: 90vh; display:flex; flex-direction: column; align-items:center; gap: 0.75rem;">
            <img :src="lightboxFile?.url" :alt="lightboxFile?.name" style="max-width: 90vw; max-height: 78vh; object-fit: contain; border-radius: 0.5rem; box-shadow: 0 8px 32px rgba(0,0,0,0.5);"/>
            <div style="display:flex; align-items:center; gap:0.75rem; flex-wrap: wrap; justify-content:center;">
                <span style="background: var(--pdc-primary); color:white; font-size:0.78rem; font-weight:600; padding:0.3rem 0.7rem; border-radius:9999px;" x-text="(lightboxIndex+1) + ' / ' + files.length"></span>
                <span style="background: var(--pdc-primary); color:white; font-size:0.78rem; font-weight:600; padding:0.3rem 0.7rem; border-radius:0.5rem; max-width: 60vw; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" x-text="lightboxFile?.name"></span>
                <span x-show="lightboxIndex===0" style="background: var(--pdc-primary); color:white; font-size:0.7rem; font-weight:700; padding:0.3rem 0.6rem; border-radius:0.4rem; letter-spacing:0.04em;">GALVENĀ</span>
            </div>
        </div>
        </div>
    </template>
</div>
