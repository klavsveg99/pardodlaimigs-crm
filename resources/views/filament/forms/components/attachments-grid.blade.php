@php
    $statePath = $getStatePath();
    $record = $getRecord();
    $isReorderable = $isReorderable();
    $isDeletable = $isDeletable();
    $isMultiselect = $isMultiselect();

    $existingAttachments = $record?->attachments?->sortBy('sort_order')?->values() ?? collect();

    // Compute the original names state path: replace last segment 'attachments' with 'attachment_original_names'
    $originalNamesPath = preg_replace('/attachments$/', 'attachment_original_names', $statePath);
@endphp

<div
    x-data="{
        files: @js($existingAttachments->map(fn ($a) => [
            'id' => $a->id,
            'path' => $a->path,
            'url' => $a->url,
            'name' => $a->original_name,
        ])->values()),
        selected: [],
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
                formData.append('_token', document.querySelector('meta[name=\"csrf-token\"]').content);
                try {
                    const resp = await fetch('{{ route(\'filament.admin.property.upload-attachment\") }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').content,
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
    class="space-y-3"
>
    {{-- Toolbar --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            @if($isMultiselect)
                <template x-if="hasSelection">
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-500 dark:text-gray-400" x-text="selected.length + ' atlasīti'"></span>
                        <button
                            type="button"
                            x-on:click="deleteSelected()"
                            class="fi-btn fi-btn-size-sm fi-color-danger fi-btn-type-outlined"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M16.5 4.478v.227a48.816 48.816 0 0 1 3.878.512.75.75 0 1 1-.256 1.478l-.209-.035-1.005 13.07a3 3 0 0 1-2.991 2.77H8.084a3 3 0 0 1-2.991-2.77L4.087 6.66l-.209.035a.75.75 0 0 1-.256-1.478A48.567 48.567 0 0 1 7.5 4.705v-.227c0-1.564 1.213-2.9 2.816-2.951a52.662 52.662 0 0 1 3.369 0c1.603.051 2.815 1.387 2.815 2.951Zm-6.136-1.452a51.196 51.196 0 0 1 3.273 0C14.39 3.05 15 3.684 15 4.478v.113a49.488 49.488 0 0 0-6 0v-.113c0-.794.609-1.428 1.364-1.452Zm-.355 5.945a.75.75 0 1 0-1.5.058l.347 9a.75.75 0 1 0 1.499-.058l-.346-9Zm5.48.058a.75.75 0 1 0-1.498-.058l-.347 9a.75.75 0 0 0 1.5.058l.345-9Z" clip-rule="evenodd"/></svg>
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
            for="attachments-upload-{{ str_replace('.', '-', $statePath) }}"
            class="fi-btn fi-btn-size-sm fi-color-primary fi-btn-type-outlined cursor-pointer"
        >
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 3.75a.75.75 0 01.75.75v6.75h6.75a.75.75 0 010 1.5h-6.75v6.75a.75.75 0 01-1.5 0v-6.75H4.5a.75.75 0 010-1.5h6.75V4.5a.75.75 0 01.75-.75z" clip-rule="evenodd"/></svg>
            <span>Pievienot</span>
        </label>
        <input
            id="attachments-upload-{{ str_replace('.', '-', $statePath) }}"
            type="file"
            multiple
            accept="{{ implode(',', config('attachments.accepted_file_types', [])) }}"
            class="hidden"
            x-on:change="handleUpload($event)"
        />
    </div>

    {{-- Image grid --}}
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
        <template x-for="(file, index) in files" :key="file.id">
            <div
                class="group relative rounded-xl border overflow-hidden aspect-video bg-gray-50 dark:bg-white/5 transition-colors"
                :class="selected.includes(file.id)
                    ? 'border-[var(--pdc-primary)] ring-2 ring-[var(--pdc-primary)]/30'
                    : 'border-gray-200 dark:border-white/10 hover:border-[var(--pdc-primary)] dark:hover:border-[var(--pdc-primary)]'"
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
                    class="w-full h-full object-cover transition-transform duration-200"
                    :class="selected.includes(file.id) ? '' : 'group-hover:scale-105'"
                />

                @if($isMultiselect)
                    <button
                        type="button"
                        x-on:click.stop="toggleSelect(file.id)"
                        class="absolute top-2 left-2 z-10 h-5 w-5 rounded border-2 flex items-center justify-center transition-colors"
                        :class="selected.includes(file.id)
                            ? 'bg-[var(--pdc-primary)] border-[var(--pdc-primary)] text-white'
                            : 'bg-white/80 dark:bg-black/50 border-gray-300 dark:border-gray-600 opacity-0 group-hover:opacity-100'"
                    >
                        <svg x-show="selected.includes(file.id)" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </button>
                @endif

                @if($isDeletable)
                    <button
                        type="button"
                        x-on:click.stop="removeFile(file.id)"
                        class="absolute top-2 right-2 z-10 h-6 w-6 rounded-full bg-black/50 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-600"
                    >
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M5.47 5.47a.75.75 0 011.06 0L12 10.94l5.47-5.47a.75.75 0 111.06 1.06L13.06 12l5.47 5.47a.75.75 0 11-1.06 1.06L12 13.06l-5.47 5.47a.75.75 0 01-1.06-1.06L10.94 12 5.47 6.53a.75.75 0 010-1.06z" clip-rule="evenodd"/></svg>
                    </button>
                @endif

                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-3">
                    <span class="text-white text-xs truncate block w-full" x-text="file.name"></span>
                </div>

                @if($isReorderable)
                    <div class="absolute top-2 right-9 z-10 opacity-0 group-hover:opacity-100 transition-opacity">
                        <div class="h-6 w-6 rounded bg-black/50 text-white flex items-center justify-center cursor-grab active:cursor-grabbing">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M3 6.75A.75.75 0 013.75 6h16.5a.75.75 0 010 1.5H3.75A.75.75 0 013 6.75zM3 12a.75.75 0 01.75-.75h16.5a.75.75 0 010 1.5H3.75A.75.75 0 013 12zm0 5.25a.75.75 0 01.75-.75h16.5a.75.75 0 010 1.5H3.75a.75.75 0 01-.75-.75z" clip-rule="evenodd"/></svg>
                        </div>
                    </div>
                @endif
            </div>
        </template>
    </div>

    {{-- Empty state --}}
    <div
        x-show="files.length === 0"
        class="text-center py-8 border-2 border-dashed border-gray-200 dark:border-white/10 rounded-xl"
    >
        <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Nav pielikumu.</p>
        <label
            for="attachments-upload-{{ str_replace('.', '-', $statePath) }}"
            class="mt-3 fi-btn fi-btn-size-sm fi-color-primary fi-btn-type-outlined cursor-pointer inline-flex"
        >
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 3.75a.75.75 0 01.75.75v6.75h6.75a.75.75 0 010 1.5h-6.75v6.75a.75.75 0 01-1.5 0v-6.75H4.5a.75.75 0 010-1.5h6.75V4.5a.75.75 0 01.75-.75z" clip-rule="evenodd"/></svg>
            <span>Pievienot failus</span>
        </label>
    </div>
</div>
