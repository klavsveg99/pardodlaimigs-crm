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
        init() {
            try { this.files = JSON.parse(document.getElementById('{{ $uid }}-data').textContent) || []; } catch(e){ this.files=[]; }
            this.uploadUrl = this.$el.dataset.uploadUrl;
            this.csrfToken = document.querySelector('meta[name=&quot;csrf-token&quot;]')?.content || document.querySelector('meta[name=csrf-token]')?.content;
            window.__pdcAttachments = this;
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
    x-on:keydown.escape.window="if(lightboxOpen) closeLightbox()"
    x-on:keydown.arrow-left.window="if(lightboxOpen) lightboxPrev()"
    x-on:keydown.arrow-right.window="if(lightboxOpen) lightboxNext()"
>
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
            @if($isMultiselect)
                <template x-if="files.length > 0">
                    <button
                        type="button"
                        x-on:click="allSelected ? clearSelection() : selectAll()"
                        class="fi-btn fi-btn-size-sm fi-color-gray fi-btn-type-outlined"
                        :class="allSelected ? 'fi-color-primary' : 'fi-color-gray'"
                    >
                        <svg style="width: 1rem; height: 1rem;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span x-text="allSelected ? 'Noņemt atlasi' : 'Atlasīt visus'"></span>
                    </button>
                </template>
                <template x-if="hasSelection">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="font-size: 0.875rem; font-weight: 600; color: var(--pdc-primary);" x-text="selected.length + ' atlasīti'"></span>
                        <button
                            type="button"
                            x-on:click="deleteSelected()"
                            class="fi-btn fi-btn-size-sm fi-color-danger fi-btn-type-outlined"
                        >
                            <svg style="width: 1rem; height: 1rem;" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M16.5 4.478v.227a48.816 48.816 0 0 1 3.878.512.75.75 0 1 1-.256 1.478l-.209-.035-1.005 13.07a3 3 0 0 1-2.991 2.77H8.084a3 3 0 0 1-2.991-2.77L4.087 6.66l-.209.035a.75.75 0 0 1-.256-1.478A48.567 48.567 0 0 1 7.5 4.705v-.227c0-1.564 1.213-2.9 2.816-2.951a52.662 52.662 0 0 1 3.369 0c1.603.051 2.815 1.387 2.815 2.951Zm-6.136-1.452a51.196 51.196 0 0 1 3.273 0C14.39 3.05 15 3.684 15 4.478v.113a49.488 49.488 0 0 0-6 0v-.113c0-.794.609-1.428 1.364-1.452Zm-.355 5.945a.75.75 0 1 0-1.5.058l.347 9a.75.75 0 1 0 1.499-.058l-.346-9Zm5.48.058a.75.75 0 1 0-1.498-.058l-.347 9a.75.75 0 0 0 1.5.058l.345-9Z" clip-rule="evenodd"/></svg>
                            <span>Dzēst atlasītos</span>
                        </button>
                        <button
                            type="button"
                            x-on:click="clearSelection()"
                            class="fi-btn fi-btn-size-sm fi-color-gray fi-btn-type-outlined"
                        >
                            <span>Atcelt</span>
                        </button>
                    </div>
                </template>
            @endif
        </div>
        <label
            for="{{ $uid }}-upload"
            class="fi-btn fi-btn-size-sm fi-color-primary fi-btn-type-outlined"
            style="cursor: pointer;"
        >
            <svg style="width: 1rem; height: 1rem;" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 3.75a.75.75 0 01.75.75v6.75h6.75a.75.75 0 010 1.5h-6.75v6.75a.75.75 0 01-1.5 0v-6.75H4.5a.75.75 0 010-1.5h6.75V4.5a.75.75 0 01.75-.75z" clip-rule="evenodd"/></svg>
            <span>Pievienot</span>
        </label>
        <input
            id="{{ $uid }}-upload"
            type="file"
            multiple
            accept="{{ implode(',', config('attachments.accepted_file_types', [])) }}"
            style="display: none;"
            x-on:change="handleUpload($event)"
        />
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
        <template x-for="(file, index) in files" :key="file.id">
            <div
                data-attach-card
                class="group"
                style="position: relative; border-radius: 0.75rem; overflow: hidden; aspect-ratio: 16/9; background: #f9fafb; transition: all 0.2s ease; cursor: grab;"
                :style="selected.includes(file.id)
                    ? 'border: 2px solid var(--pdc-primary); box-shadow: 0 0 0 3px rgba(40,88,84,0.2);'
                    : 'border: 1px solid #e5e7eb;'"
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
                        style="position: absolute; top: 0.5rem; left: 0.5rem; z-index: 10; height: 1.4rem; width: 1.4rem; border-radius: 0.35rem; border: 2px solid; display: flex; align-items: center; justify-content: center; transition: all 0.2s; box-shadow: 0 1px 4px rgba(0,0,0,0.35); cursor: pointer;"
                        :style="selected.includes(file.id)
                            ? 'background: var(--pdc-primary); border-color: var(--pdc-primary); color: white;'
                            : 'background: rgba(255,255,255,0.96); border-color: #6b7280; color: transparent;'"
                    >
                        <svg x-show="selected.includes(file.id)" style="width: 0.85rem; height: 0.85rem;" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </button>
                @endif

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
                    <div class="opacity-60 group-hover:opacity-100 transition-opacity" style="position: absolute; top: 0.5rem; right: 2.4rem; z-index: 10; pointer-events: none;">
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
        <svg style="margin: 0 auto; height: 3rem; width: 3rem; color: #9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <p style="margin-top: 0.75rem; font-size: 0.875rem; color: #6b7280;">Nav pielikumu.</p>
        <label
            for="{{ $uid }}-upload"
            style="margin-top: 0.75rem; display: inline-flex; cursor: pointer;"
            class="fi-btn fi-btn-size-sm fi-color-primary fi-btn-type-outlined"
        >
            <svg style="width: 1rem; height: 1rem;" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 3.75a.75.75 0 01.75.75v6.75h6.75a.75.75 0 010 1.5h-6.75v6.75a.75.75 0 01-1.5 0v-6.75H4.5a.75.75 0 010-1.5h6.75V4.5a.75.75 0 01.75-.75z" clip-rule="evenodd"/></svg>
            <span>Pievienot failus</span>
        </label>
    </div>

    <!-- Lightbox Gallery -->
    <div
        x-show="lightboxOpen"
        x-transition.opacity
        style="position: fixed; inset: 0; z-index: 99999; display: none; align-items: center; justify-content: center; background: rgba(0,0,0,0.92); padding: 1rem;"
        x-bind:style="lightboxOpen ? 'display:flex;' : 'display:none;'"
        x-on:click.self="closeLightbox()"
    >
        <button type="button" x-on:click="closeLightbox()" style="position: absolute; top: 1rem; right: 1rem; z-index: 10; width: 2.5rem; height: 2.5rem; border-radius: 9999px; background: rgba(255,255,255,0.12); color: white; border: 1px solid rgba(255,255,255,0.2); display:flex; align-items:center; justify-content:center; cursor:pointer; backdrop-filter: blur(4px);">
            <svg style="width: 1.25rem; height:1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <button type="button" x-show="files.length > 1" x-on:click.stop="lightboxPrev()" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); z-index:10; width: 2.75rem; height:2.75rem; border-radius: 9999px; background: rgba(255,255,255,0.12); color:white; border:1px solid rgba(255,255,255,0.2); display:flex; align-items:center; justify-content:center; cursor:pointer; backdrop-filter: blur(4px);">
            <svg style="width:1.4rem;height:1.4rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <button type="button" x-show="files.length > 1" x-on:click.stop="lightboxNext()" style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); z-index:10; width: 2.75rem; height:2.75rem; border-radius: 9999px; background: rgba(255,255,255,0.12); color:white; border:1px solid rgba(255,255,255,0.2); display:flex; align-items:center; justify-content:center; cursor:pointer; backdrop-filter: blur(4px);">
            <svg style="width:1.4rem;height:1.4rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
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
</div>
