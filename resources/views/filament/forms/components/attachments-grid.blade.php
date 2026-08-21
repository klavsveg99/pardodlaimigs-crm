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

<div
    x-data="{
        files: [],
        selected: [],
        uploadUrl: null,
        csrfToken: null,
        init() {
            this.files = JSON.parse(document.getElementById('{{ $uid }}-data').textContent);
            this.uploadUrl = this.$el.dataset.uploadUrl;
            this.csrfToken = document.querySelector('meta[name=&quot;csrf-token&quot;]').content;
        },
        get hasSelection() { return this.selected.length > 0 },
        get paths() { return this.files.map(f => f.path) },
        get names() {
            const map = {};
            this.files.forEach(f => { map[f.path] = f.name; });
            return map;
        },
        sync() {
            $wire.set('{{ $statePath }}', this.paths);
            $wire.set('{{ $originalNamesPath }}', this.names);
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
            this.files = this.files.filter(f => !this.selected.includes(f.id));
            this.selected = [];
            this.sync();
        },
        removeFile(id) {
            this.files = this.files.filter(f => f.id !== id);
            this.sync();
        },
        dragIndex: null,
        onDragStart(e, index) {
            this.dragIndex = index;
            e.dataTransfer.effectAllowed = 'move';
        },
        onDragOver(e, index) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
        },
        onDrop(e, index) {
            e.preventDefault();
            if (this.dragIndex === null || this.dragIndex === index) return;
            const item = this.files.splice(this.dragIndex, 1)[0];
            this.files.splice(index, 0, item);
            this.dragIndex = null;
            this.sync();
        },
        onDragEnd() { this.dragIndex = null; },
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
    wire:ignore
    data-upload-url="{{ $uploadUrl }}"
>
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            @if($isMultiselect)
                <template x-if="hasSelection">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="font-size: 0.875rem; color: #6b7280;" x-text="selected.length + ' atlasīti'"></span>
                        <button
                            type="button"
                            x-on:click="deleteSelected()"
                            class="fi-btn fi-btn-size-sm fi-color-danger fi-btn-type-outlined"
                        >
                            <svg style="width: 1rem; height: 1rem;" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M16.5 4.478v.227a48.816 48.816 0 0 1 3.878.512.75.75 0 1 1-.256 1.478l-.209-.035-1.005 13.07a3 3 0 0 1-2.991 2.77H8.084a3 3 0 0 1-2.991-2.77L4.087 6.66l-.209.035a.75.75 0 0 1-.256-1.478A48.567 48.567 0 0 1 7.5 4.705v-.227c0-1.564 1.213-2.9 2.816-2.951a52.662 52.662 0 0 1 3.369 0c1.603.051 2.815 1.387 2.815 2.951Zm-6.136-1.452a51.196 51.196 0 0 1 3.273 0C14.39 3.05 15 3.684 15 4.478v.113a49.488 49.488 0 0 0-6 0v-.113c0-.794.609-1.428 1.364-1.452Zm-.355 5.945a.75.75 0 1 0-1.5.058l.347 9a.75.75 0 1 0 1.499-.058l-.346-9Zm5.48.058a.75.75 0 1 0-1.498-.058l-.347 9a.75.75 0 0 0 1.5.058l.345-9Z" clip-rule="evenodd"/></svg>
                            <span>Dzēst</span>
                        </button>
                        <button
                            type="button"
                            x-on:click="clearSelection()"
                            class="fi-btn fi-btn-size-sm fi-color-gray fi-btn-type-outlined"
                        >
                            <span>Atcelt izvēli</span>
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

    <div style="display: grid; gap: 0.75rem; grid-template-columns: repeat(5, 1fr);">
        <template x-for="(file, index) in files" :key="file.id">
            <div
                style="position: relative; border-radius: 0.75rem; overflow: hidden; aspect-ratio: 16/9; background: #f9fafb; transition: border-color 0.2s;"
                :style="selected.includes(file.id)
                    ? 'border: 2px solid var(--pdc-primary); box-shadow: 0 0 0 3px rgba(40,88,84,0.2);'
                    : 'border: 1px solid #e5e7eb;'"
                @if($isReorderable) draggable="true"
                x-on:dragstart="onDragStart($event, index)"
                x-on:dragover="onDragOver($event, index)"
                x-on:drop="onDrop($event, index)"
                x-on:dragend="onDragEnd()"
                @endif
            >
                <img
                    :src="file.url"
                    :alt="file.name"
                    style="width: 100%; height: 100%; object-fit: cover;"
                />

                @if($isMultiselect)
                    <button
                        type="button"
                        x-on:click.stop="toggleSelect(file.id)"
                        style="position: absolute; top: 0.5rem; left: 0.5rem; z-index: 10; height: 1.25rem; width: 1.25rem; border-radius: 0.25rem; border: 2px solid; display: flex; align-items: center; justify-content: center; transition: all 0.2s;"
                        :style="selected.includes(file.id)
                            ? 'background: var(--pdc-primary); border-color: var(--pdc-primary); color: white;'
                            : 'background: rgba(255,255,255,0.8); border-color: #d1d5db; color: transparent;'"
                    >
                        <svg x-show="selected.includes(file.id)" style="width: 0.75rem; height: 0.75rem;" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </button>
                @endif

                @if($isDeletable)
                    <button
                        type="button"
                        x-on:click.stop="removeFile(file.id)"
                        style="position: absolute; top: 0.5rem; right: 0.5rem; z-index: 10; height: 1.5rem; width: 1.5rem; border-radius: 9999px; background: rgba(0,0,0,0.5); color: white; display: flex; align-items: center; justify-content: center; border: none; cursor: pointer;"
                    >
                        <svg style="width: 0.875rem; height: 0.875rem;" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M5.47 5.47a.75.75 0 011.06 0L12 10.94l5.47-5.47a.75.75 0 111.06 1.06L13.06 12l5.47 5.47a.75.75 0 11-1.06 1.06L12 13.06l-5.47 5.47a.75.75 0 01-1.06-1.06L10.94 12 5.47 6.53a.75.75 0 010-1.06z" clip-rule="evenodd"/></svg>
                    </button>
                @endif

                <div style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.7), transparent, transparent); opacity: 0; display: flex; align-items: flex-end; padding: 0.75rem; transition: opacity 0.2s; pointer-events: none;">
                    <span style="color: white; font-size: 0.75rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: block; width: 100%;" x-text="file.name"></span>
                </div>

                @if($isReorderable)
                    <div style="position: absolute; top: 0.5rem; right: 2.25rem; z-index: 10; opacity: 0; transition: opacity 0.2s; pointer-events: none;">
                        <div style="height: 1.5rem; width: 1.5rem; border-radius: 0.25rem; background: rgba(0,0,0,0.5); color: white; display: flex; align-items: center; justify-content: center; cursor: grab;">
                            <svg style="width: 0.875rem; height: 0.875rem;" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M3 6.75A.75.75 0 013.75 6h16.5a.75.75 0 010 1.5H3.75A.75.75 0 013 6.75zM3 12a.75.75 0 01.75-.75h16.5a.75.75 0 010 1.5H3.75A.75.75 0 013 12zm0 5.25a.75.75 0 01.75-.75h16.5a.75.75 0 010 1.5H3.75a.75.75 0 01-.75-.75z" clip-rule="evenodd"/></svg>
                        </div>
                    </div>
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
</div>
