@php
    $statePath = $getStatePath();
    $record = $getRecord();
    $isReorderable = $isReorderable();
    $isDeletable = $isDeletable();
    $isMultiselect = $isMultiselect();
    // "Lejupielādēt visus" ZIP — tikai īpašuma galerijai ar saglabātu ierakstu.
    $galleryZipUrl = ($isDownloadable() && $record instanceof \App\Models\CrmProperty)
        ? route('properties.gallery.download', ['propertySlug' => $record->slug ?? $record->getKey()])
        : null;
    // The layout follows the field configuration (client/viewing/task and
    // property-document grids use rows), independent of whether send actions
    // are currently possible — on the create page the files should already
    // render as rows, exactly like they will after the record is saved.
    $isRowMode = $isSendable() || $isPropertySendable() || $isRecordSendable();

    $isSendable = $isSendable();
    $isPropertySendable = $isPropertySendable();
    $isRecordSendable = $isRecordSendable();
    // Sending needs a persisted owner to build upload/send URLs; on the
    // create page there is no record yet, so the send actions stay off while
    // files are uploaded generically and attached after the record is created.
    if (! $record) {
        $isSendable = false;
        $isPropertySendable = false;
        $isRecordSendable = false;
    }
    // ViewRecord publication renders the same field read-only.
    $isView = (fn () => $getContainer()->getOperation() === 'view')();

    $collection = $getCollection();
    $existingAttachments = collect($record?->attachments ?? [])
        ->where('collection', $collection)
        ->sortBy('sort_order')
        ->values();

    // Associated clients (properties only): the email recipient must be one
    // of them, and with none associated the send buttons are not shown.
    $propertyClients = ($isPropertySendable && $record instanceof \App\Models\CrmProperty)
        ? $record->clients->map(fn ($c) => [
            'id' => $c->id,
            'name' => (string) $c->name,
            'email' => (string) ($c->email ?? ''),
            'phone' => (string) ($c->phone ?? ''),
        ])->values()->all()
        : [];
    // Records owned by a client (viewings/tasks): recipient from the
    // associated client; no send buttons without one. Property sending is an
    // edit-page action only — the property view page shows a read-only list.
    $recordClient = $isRecordSendable ? $record?->client : null;
    $canSend = $isPropertySendable
        ? (count($propertyClients) > 0 && ! $isView)
        : ($isRecordSendable ? (bool) $recordClient : $isSendable);

    // Mark files that were previously emailed ("Nosūtīts klientam") from the
    // audit activity log — client-scoped for clients, property-scoped for
    // properties, owner-scoped for viewings/tasks.
    $recordOwnerKey = $isRecordSendable && $record
        ? (($record instanceof \App\Models\Viewing ? 'viewing' : 'task').':'.$record->getKey())
        : null;

    $sentAtByAttachment = [];
    if ($record && ($isSendable || $isPropertySendable || $isRecordSendable)) {
        \App\Models\Activity::query()
            ->where('type', 'attachment_email_sent')
            ->when(
                $isPropertySendable,
                fn ($q) => $q->where('property_id', $record->id),
                fn ($q) => $q->where('client_id', $isRecordSendable ? $recordClient?->id : $record->id),
            )
            ->orderBy('created_at')
            ->get()
            ->each(function ($activity) use (&$sentAtByAttachment, $isRecordSendable, $recordOwnerKey): void {
                if ($isRecordSendable && ($activity->payload['owner'] ?? null) !== $recordOwnerKey) {
                    return;
                }
                foreach ((array) ($activity->payload['files'] ?? []) as $attachmentId) {
                    if (is_int($attachmentId)) {
                        $sentAtByAttachment[$attachmentId] = $activity->created_at?->format('d.m.Y H:i');
                    }
                }
            });
    }

    $attachmentsJson = $existingAttachments->map(fn ($a) => [
        'id' => $a->id,
        'path' => $a->path,
        'url' => $a->cacheBustedUrl(),
        'name' => $a->original_name,
        'mime' => $a->mime_type,
        'size' => $a->size,
        'sentAt' => $sentAtByAttachment[$a->id] ?? null,
        'created' => $a->created_at?->format('d.m.Y'),
    ])->values()->toJson();

    $originalNamesPath = preg_replace('/attachments$/', 'attachment_original_names', $statePath);
    $uid = 'att-' . str_replace('.', '-', $statePath);
    $uploadUrl = route('filament.admin.property.upload-attachment');
    $proxyUrl = route('filament.admin.property.image-proxy');

    // WhatsApp chat link from a phone ("+371 24248764", "24248764"…).
    $waPhone = static function (?string $phone): string {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if (strlen($digits) === 8) {
            $digits = '371'.$digits;
        }

        return $digits !== '' ? 'https://wa.me/'.$digits : '';
    };
    $waLink = $waPhone($record?->phone);

    // Endpoints / recipient defaults per context. The popup itself is a
    // shared partial (resources/views/filament/partials/attachment-send-popup).
    $recordSegment = null;
    if ($isRecordSendable && $record) {
        $recordSegment = $record instanceof \App\Models\Viewing ? 'viewings' : 'tasks';
    }

    $mailSendUrl = null;
    $mailDefaultTo = '';
    $mailBaseWa = '';
    if ($isSendable) {
        $mailSendUrl = route('clients.attachments.send-email', ['clientSlug' => $record?->slug ?? $record?->getKey()]);
        $mailDefaultTo = (string) ($record?->email ?? '');
        $mailBaseWa = $waLink;
    } elseif ($isPropertySendable && $record) {
        $mailSendUrl = route('properties.attachments.send-email', ['propertySlug' => $record->slug ?? $record->getKey()]);
    } elseif ($recordSegment && $record) {
        $mailSendUrl = route($recordSegment.'.attachments.send-email', ['id' => $record->getKey()]);
        $mailDefaultTo = (string) ($recordClient?->email ?? '');
        $mailBaseWa = $waPhone($recordClient?->phone);
    }

    // Blade @if inside an HTML tag breaks the tag when Livewire wraps the
    // directive in an HTML comment (the ">" terminates the tag early), so
    // conditional attributes are precomputed and echoed as plain markup.
    $sendDataAttrs = '';
    if ($isSendable) {
        $sendDataAttrs = 'data-client-slug="'.e($record?->slug ?? $record?->getKey()).'" '
            .'data-upload-url-client="'.e(route('clients.attachments.upload', ['clientSlug' => $record?->slug ?? $record?->getKey()])).'" '
            .'data-delete-url="'.e(route('clients.attachments.destroy', ['clientSlug' => $record?->slug ?? $record?->getKey(), 'attachment' => ':id'])).'"';
    } elseif ($isPropertySendable && $record) {
        $sendDataAttrs = 'data-upload-url-property="'.e(route('properties.attachments.upload', ['propertySlug' => $record->slug ?? $record->getKey()])).'" '
            .'data-delete-url="'.e(route('properties.attachments.destroy', ['propertySlug' => $record->slug ?? $record->getKey(), 'attachment' => ':id'])).'"';
    } elseif ($recordSegment && $record) {
        $sendDataAttrs = 'data-upload-url-record="'.e(route($recordSegment.'.attachments.upload', ['id' => $record->getKey()])).'" '
            .'data-delete-url="'.e(route($recordSegment.'.attachments.destroy', ['id' => $record->getKey(), 'attachment' => ':id'])).'"';
    }

    $cardDragAttrs = $isReorderable
        ? 'draggable="true" '
            .'x-on:dragstart="onDragStart($event, index)" '
            .'x-on:dragover="onDragOver($event, index)" '
            .'x-on:dragleave="onDragLeave($event)" '
            .'x-on:drop="onDrop($event, index)" '
            .'x-on:dragend="onDragEnd($event)" '
            .'x-on:touchstart.passive="onTouchStart($event, index)" '
            .'x-on:touchmove="onTouchMove($event)" '
            .'x-on:touchend="onTouchEnd($event, index)"'
        : '';
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
    @keyframes spin { to { transform: rotate(360deg); } }
</style>

<div
    x-data="{
        files: [],
        selected: [],
        uploadUrl: null,
        proxyUrl: null,
        csrfToken: null,
        draggedIndex: null,
        lightboxOpen: false,
        lightboxIndex: 0,
        uploading: [],
        savingCrop: false,
        cropProgress: 0,
        editorOpen: false,
        editorIndex: null,
        editorFile: null,
        cropper: null,
        editorAspectRatio: null,
        scaleX: 1,
        scaleY: 1,
        // Opens the shared send popup (partial) with this file preselected.
        sendFile(file) {
            if (!file || typeof file.id !== 'number') return;
            const all = this.files
                .filter(f => typeof f.id === 'number')
                .map(f => ({ id: f.id, name: f.name, size: f.size }));
            window.dispatchEvent(new CustomEvent('pdc-open-send', {
                detail: { file: { id: file.id, name: file.name }, all },
            }));
        },
        // Marks files as sent (green Nosūtīts klientam badge) after the popup sends.
        markSent(detail) {
            if (!detail) return;
            (detail.marked || []).forEach(id => {
                const f = this.files.find(x => x.id === id);
                if (f) { f.sentAt = detail.sentAt || ''; }
            });
        },
        init() {
            try { this.files = JSON.parse(document.getElementById('{{ $uid }}-data').textContent) || []; } catch(e){ this.files=[]; }
            this.uploadUrl = this.$el.dataset.uploadUrlClient || this.$el.dataset.uploadUrlProperty || this.$el.dataset.uploadUrlRecord || this.$el.dataset.uploadUrl;
            this.deleteUrl = this.$el.dataset.deleteUrl || '';
            this.proxyUrl = this.$el.dataset.proxyUrl || null;
            this.csrfToken = document.querySelector('meta[name=&quot;csrf-token&quot;]')?.content || document.querySelector('meta[name=csrf-token]')?.content;
            this._waLink = this.$el.dataset.waLink || '';
            this._root = this.$el;
            window.__pdcAttachments = this;
            // Load cropper.js if not already loaded
            if (!window.Cropper && !document.querySelector('script[data-cropper]')) {
                const s = document.createElement('script');
                s.src = 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js';
                s.setAttribute('data-cropper','1');
                document.head.appendChild(s);
            }
        },
        thumbUrl(file) {
            if (!file || !file.url) return '';
            const u = file.url;
            // WP uploads: serve the WP thumbnail if it exists (matches existing WP pattern)
            const wpMatch = u.match(/\/wp-content\/uploads\/(\d{4})\/(\d{2})\/(.+?)(?:\?|$)/);
            if (wpMatch) {
                const [, y, m, base] = wpMatch;
                const dot = base.lastIndexOf('.');
                if (dot > 0) {
                    const stem = base.slice(0, dot);
                    const ext = base.slice(dot);
                    return `https://pardodlaimigs.lv/wp-content/uploads/${y}/${m}/${stem}-400x300${ext}`;
                }
            }
            // Laravel /storage/ attachments: use the thumbnail file (thumb-*) if present
            const storageMatch = u.match(/\/storage\/attachments\/(.+?)(?:\?|$)/);
            if (storageMatch) {
                const base = storageMatch[1];
                const thumbPath = base.replace(/(^|\/)([^\/]+?)(\.\w+)?$/i, (m, p1, p2, p3) => p1 + 'thumb-' + p2 + (p3 ? p3 : ''));
                return window.location.origin + '/storage/attachments/' + thumbPath;
            }
            return u;
        },
        onImgError(e, file) {
            if (e.target.dataset.fallback) return;
            e.target.dataset.fallback = '1';
            e.target.src = file.url;
        },
        get hasSelection() { return this.selected.length > 0 },
        get allSelected() { return this.files.length > 0 && this.selected.length === this.files.length },
        get paths() { return this.files.map(f => f.path) },
        get names() {
            const map = {};
            this.files.forEach(f => { map[f.path] = f.name; });
            return map;
        },
        isImage(file) {
            return file && (file.mime || '').startsWith('image/');
        },
        sizeLabel(bytes) {
            if (!bytes) return '';
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return Math.round(bytes / 1024) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(1).replace('.0', '') + ' MB';
        },
        sync() {
            $wire.set('{{ $statePath }}', this.paths, false);
            $wire.set('{{ $originalNamesPath }}', this.names, false);
            // Make sure the change reaches the record promptly: pages using
            // AutosavesForm listen for this event (others ignore it harmlessly).
            clearTimeout(window.__pdcAttSyncTimer);
            this.$nextTick(() => { $wire.$refresh && $wire.$refresh(); });
            window.__pdcAttSyncTimer = setTimeout(() => {
                $wire.dispatch && $wire.dispatch('pdc-autosave-tick');
            }, 400);
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
        async removeFile(id) {
            if (!confirm('Dzēst šo failu?')) return;

            // Client / viewing / task attachments are persisted immediately,
            // so deleting must hit the server (works on edit and view pages).
            if (this.deleteUrl && typeof id === 'number') {
                try {
                    const resp = await fetch(this.deleteUrl.replace(':id', String(id)), {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    if (!resp.ok) {
                        const d = await resp.json().catch(() => ({}));
                        alert(d.message || 'Neizdevās izdzēst failu.');
                        return;
                    }
                } catch (e) {
                    alert('Neizdevās izdzēst failu: ' + (e.message || ''));
                    return;
                }
            }

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
        // Shared insertion: drop position is before/after the hovered card
        // depending on which half of the card the pointer is over. A plain
        // insert-before-hovered-card can never move an item one step to the
        // right (dropping onto the right neighbor re-inserts at the same spot).
        moveItem(from, index, clientX, clientY, cardEl) {
            if (from === null || from === index) return false;
            let after = false;
            if (cardEl && typeof clientX === 'number' && typeof clientY === 'number') {
                const rect = cardEl.getBoundingClientRect();
                if (rect.width > 0 && rect.height > 0) {
                    const afterX = (clientX - rect.left) >= rect.width / 2;
                    const afterY = (clientY - rect.top) >= rect.height / 2;
                    // Forward in order (right/down in the grid): either half past
                    // the middle means after. Backward: only the far corner.
                    after = (index > from) ? (afterX || afterY) : (afterX && afterY);
                }
            } else if (from < index) {
                after = true;
            }
            const item = this.files.splice(from, 1)[0];
            let to = after ? index + 1 : index;
            if (from < to) to -= 1; // compensate the removal shift
            this.files.splice(Math.max(0, Math.min(to, this.files.length)), 0, item);
            return true;
        },
        onDrop(e, index) {
            e.preventDefault();
            const card = e.currentTarget;
            card.classList.remove('pdc-drag-over');
            const from = this.draggedIndex;
            this.draggedIndex = null;
            if (this.moveItem(from, index, e.clientX, e.clientY, card)) {
                this.sync();
            }
        },
        onDragEnd(e) {
            e.currentTarget.classList.remove('pdc-dragging');
            this.draggedIndex = null;
            this.$el.querySelectorAll('[data-attach-card]').forEach(el => el.classList.remove('pdc-drag-over','pdc-dragging'));
        },
        touchDragId: null,
        touchStartY: 0,
        onTouchStart(e, index) {
            if (e.touches.length !== 1) return;
            this.touchDragId = index;
            this.touchStartY = e.touches[0].clientY;
            e.currentTarget.classList.add('pdc-dragging');
        },
        onTouchMove(e) {
            if (this.touchDragId === null || e.touches.length !== 1) return;
            e.preventDefault();
            const el = document.elementFromPoint(e.touches[0].clientX, e.touches[0].clientY);
            if (!el) return;
            const card = el.closest('[data-attach-card]');
            this.$el.querySelectorAll('[data-attach-card]').forEach(c => c.classList.remove('pdc-drag-over'));
            if (card) card.classList.add('pdc-drag-over');
        },
        onTouchEnd(e, index) {
            if (this.touchDragId === null) return;
            const t = (e.changedTouches && e.changedTouches[0]) || null;
            let targetIndex = index;
            let targetCard = null;
            if (t) {
                const el = document.elementFromPoint(t.clientX, t.clientY);
                if (el) {
                    const card = el.closest('[data-attach-card]');
                    if (card) {
                        targetCard = card;
                        const idxAttr = Array.from(this.$el.querySelectorAll('[data-attach-card]')).indexOf(card);
                        if (idxAttr >= 0) targetIndex = idxAttr;
                    }
                }
            }
            const from = this.touchDragId;
            this.touchDragId = null;
            this.$el.querySelectorAll('[data-attach-card]').forEach(c => c.classList.remove('pdc-drag-over','pdc-dragging'));
            if (this.moveItem(from, targetIndex, t ? t.clientX : null, t ? t.clientY : null, targetCard)) {
                this.sync();
            }
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
        editUrl(file) {
            if (!file || !file.url) return '';
            try {
                const u = new URL(file.url, window.location.origin);
                if (u.origin !== window.location.origin && this.proxyUrl) {
                    return this.proxyUrl + '?url=' + encodeURIComponent(file.url);
                }
            } catch(e) {}
            return file.url;
        },
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
                img.src = this.editUrl(this.editorFile);
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
            if (!this.cropper || this.editorIndex === null || this.savingCrop) return;
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
            this.savingCrop = true;
            this.cropProgress = 0;
            const mime = this.editorFile.name.match(/\.png$/i) ? 'image/png' : 'image/jpeg';
            canvas.toBlob((blob) => {
                if (!blob) {
                    this.savingCrop = false;
                    alert('Neizdevās saglabāt attēlu.');
                    return;
                }
                const ext = (this.editorFile.name.split('.').pop() || 'jpg').toLowerCase();
                const fileName = this.editorFile.name.replace(/\.[^/.]+$/, '') + '-crop.' + ext;
                const file = new File([blob], fileName, { type: blob.type || mime });
                const xhr = new XMLHttpRequest();
                xhr.open('POST', this.uploadUrl, true);
                xhr.setRequestHeader('X-CSRF-TOKEN', this.csrfToken);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.upload.addEventListener('progress', (ev) => {
                    if (ev.lengthComputable) {
                        this.cropProgress = Math.round(ev.loaded / ev.total * 100);
                    }
                });
                xhr.addEventListener('load', () => {
                    try {
                        const r = JSON.parse(xhr.responseText);
                        if (r.path) {
                            this.files[this.editorIndex] = {
                                id: this.editorFile.id,
                                path: r.path,
                                url: r.url,
                                name: r.name || fileName,
                            };
                            this.sync();
                            this.closeEditor();
                        } else {
                            window.alert('Augšupielāde neizdevās: ' + fileName);
                        }
                    } catch (err) {
                        window.alert('Augšupielāde neizdevās: ' + fileName + '\n' + err.message);
                    }
                });
                xhr.addEventListener('error', () => {
                    window.alert('Augšupielāde neizdevās: ' + fileName);
                });
                xhr.addEventListener('loadend', () => {
                    this.savingCrop = false;
                    this.cropProgress = 0;
                });
                const formData = new FormData();
                formData.append('file', file);
                formData.append('_token', this.csrfToken);
                xhr.send(formData);
            }, mime, 0.92);
        },
        pickFiles() {
            this.$refs.fileInput && this.$refs.fileInput.click();
        },
        async handleUpload(e) {
            const input = e.target || e;
            const newFiles = Array.from(input.files || []);
            if (!newFiles.length) return;
            for (const file of newFiles) {
                const trackObj = {
                    id: 'up-' + Date.now() + '-' + Math.random().toString(36).slice(2, 8),
                    name: file.name,
                    progress: 0,
                    size: file.size,
                };
                this.uploading.push(trackObj);
                const xhr = new XMLHttpRequest();
                xhr.open('POST', this.uploadUrl, true);
                xhr.setRequestHeader('X-CSRF-TOKEN', this.csrfToken);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.upload.addEventListener('progress', (ev) => {
                    if (ev.lengthComputable) {
                        const idx = this.uploading.findIndex(u => u.id === trackObj.id);
                        if (idx !== -1) this.uploading[idx].progress = Math.round(ev.loaded / ev.total * 100);
                    }
                });
                xhr.addEventListener('load', () => {
                    try {
                        const r = JSON.parse(xhr.responseText);
                        if (r.path) {
                            this.files.push({
                                id: (r.id !== undefined ? r.id : 'new-' + Date.now() + '-' + Math.random().toString(36).slice(2, 8)),
                                path: r.path,
                                url: r.url,
                                name: r.name || file.name,
                                mime: file.type || '',
                                size: r.size || file.size,
                                created: r.created || (() => { const d = new Date(); return String(d.getDate()).padStart(2, '0') + '.' + String(d.getMonth() + 1).padStart(2, '0') + '.' + d.getFullYear(); })(),
                            });
                            this.sync();
                        } else {
                            window.alert('Augšupielāde neizdevās: ' + file.name);
                        }
                    } catch (err) {
                        window.alert('Augšupielāde neizdevās: ' + file.name + '\n' + err.message);
                    }
                });
                xhr.addEventListener('error', () => {
                    window.alert('Augšupielāde neizdevās: ' + file.name);
                });
                xhr.addEventListener('loadend', () => {
                    this.uploading = this.uploading.filter(u => u.id !== trackObj.id);
                });
                const formData = new FormData();
                formData.append('file', file);
                formData.append('_token', this.csrfToken);
                xhr.send(formData);
            }
            input.value = '';
        }
    }"
    wire:ignore.self
    data-upload-url="{{ $uploadUrl }}"
    data-proxy-url="{{ $proxyUrl }}"
    {!! $sendDataAttrs !!}
    x-on:pdc-attachment-sent.window="markSent($event.detail)"
    x-on:keydown.escape.window="if(lightboxOpen) closeLightbox(); if(editorOpen) closeEditor()"
    x-on:keydown.arrow-left.window="if(lightboxOpen) lightboxPrev()"
    x-on:keydown.arrow-right.window="if(lightboxOpen) lightboxNext()"
>
    <input
        type="file"
        id="{{ $uid }}-upload"
        x-ref="fileInput"
        multiple
        accept="{{ implode(',', config('attachments.accepted_file_types', ['image/*'])) }}"
        style="display: none;"
        x-on:change="handleUpload($event)"
    />
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
            @if($isMultiselect && !$isView)
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
                    <button
                        type="button"
                        x-on:click="pickFiles()"
                        class="fi-btn fi-size-sm fi-color fi-color-primary"
                        style="cursor: pointer; display: inline-flex; align-items: center; gap: 0.35rem; flex-direction: row; white-space: nowrap;"
                    >
                        <svg style="width: 1rem; height: 1rem;" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 3.75a.75.75 0 01.75.75v6.75h6.75a.75.75 0 010 1.5h-6.75v6.75a.75.75 0 01-1.5 0v-6.75H4.5a.75.75 0 010-1.5h6.75V4.5a.75.75 0 01.75-.75z" clip-rule="evenodd"/></svg>
                        <span>Pievienot failus</span>
                    </button>
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
            @if($galleryZipUrl)
                <a
                    href="{{ $galleryZipUrl }}"
                    x-show="files.length > 0"
                    class="fi-btn fi-size-sm fi-color fi-color-gray fi-outlined"
                    style="cursor: pointer; display: inline-flex; align-items: center; gap: 0.35rem; flex-direction: row; white-space: nowrap;"
                >
                    <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2m-16-5l8 8 8-8m-16 0V4h16v10"/></svg>
                    <span>Lejupielādēt visus</span>
                </a>
            @endif
        </div>
    </div>

    @if(! $isRowMode)
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
        <template x-for="(file, index) in files" :key="file.id">
            <div
                data-attach-card
                class="group"
                style="position: relative; border-radius: 0.75rem; overflow: hidden; aspect-ratio: 16/9; background: #f9fafb; transition: all 0.2s ease; cursor: grab; border: 1px solid #e5e7eb;"
                :style="{ border: selected.includes(file.id) ? '2px solid var(--pdc-primary)' : '1px solid #e5e7eb', boxShadow: selected.includes(file.id) ? '0 0 0 3px rgba(40,88,84,0.2)' : 'none' }"
                {!! $cardDragAttrs !!}
                x-on:click="if (! $event.target.closest('button')) openLightbox(index)"
                title="Velc, lai pārkārtotu • Klikšķini, lai apskatītu"
                style="touch-action: none;"
            >
                <template x-if="isImage(file)">
                    <img
                        :src="thumbUrl(file)"
                        :alt="file.name"
                        loading="lazy"
                        decoding="async"
                        width="400"
                        height="300"
                        x-on:error="onImgError($event, file)"
                        style="width: 100%; height: 100%; object-fit: cover; pointer-events: none; background: #f3f4f6;"
                        draggable="false"
                    />
                </template>
                <template x-if="!isImage(file)">
                    <div style="width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.35rem; pointer-events: none; color: #6b7280;">
                        <svg style="width: 2rem; height: 2rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                        </svg>
                        <span style="font-size: 0.7rem; text-align: center; padding: 0 0.5rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 100%;" x-text="file.name"></span>
                    </div>
                </template>

                @if($isMultiselect && !$isView)
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

                @if(!$isView)
                    <button
                        type="button"
                        x-on:click.stop="openEditor(index)"
                        style="position: absolute; top: 0.5rem; right: 2.7rem; z-index: 10; height: 1.6rem; width: 1.6rem; border-radius: 0.35rem; background: rgba(0,0,0,0.62); color: white; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,0.22); cursor: pointer; backdrop-filter: blur(2px);"
                        title="Rediģēt attēlu"
                    >
                        <svg style="width: 0.85rem; height: 0.85rem;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.83 15.83a2 2 0 01-1.415.586H9a1 1 0 01-1-1v-1.415a2 2 0 01.586-1.414l9-9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 5l4 4"/></svg>
                    </button>
                @endif

                @if($isDeletable && !$isView)
                    <button
                        type="button"
                        x-on:click.stop="removeFile(file.id)"
                        style="position: absolute; top: 0.5rem; right: 0.5rem; z-index: 10; height: 1.6rem; width: 1.6rem; border-radius: 9999px; background: rgba(0,0,0,0.55); color: white; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,0.3); cursor: pointer; backdrop-filter: blur(2px);"
                    >
                        <svg style="width: 0.9rem; height: 0.9rem;" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M5.47 5.47a.75.75 0 011.06 0L12 10.94l5.47-5.47a.75.75 0 111.06 1.06L13.06 12l5.47 5.47a.75.75 0 11-1.06 1.06L12 13.06l-5.47 5.47a.75.75 0 01-1.06-1.06L10.94 12 5.47 6.53a.75.75 0 010-1.06z" clip-rule="evenodd"/></svg>
                    </button>
                @endif

                @if($isSendable || ($isPropertySendable && $canSend))
                    <button
                        type="button"
                        x-bind:style="{ display: typeof file.id === 'number' ? 'inline-flex' : 'none' }"
                        x-on:click.stop="sendFile(file)"
                        title="Nosūtīt ar e-pastu"
                        style="position: absolute; bottom: 0.5rem; right: 0.5rem; z-index: 10; height: 1.7rem; padding: 0 0.55rem; border-radius: 0.4rem; background: var(--pdc-primary); color: white; display: inline-flex; flex-direction: row; flex-wrap: nowrap; align-items: center; gap: 0.3rem; white-space: nowrap; border: 1px solid rgba(255,255,255,0.3); cursor: pointer; font-size: 0.72rem; font-weight: 600;"
                    >
                        <svg style="width: 0.85rem; height: 0.85rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                        <span>Nosūtīt</span>
                    </button>
                @endif

                <!-- Title on hover - accent container -->
                <div style="position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.55) 0%, transparent 45%); opacity: 0; display: flex; align-items: flex-end; padding: 0.6rem; transition: opacity 0.2s; pointer-events: none;" class="group-hover:opacity-100" x-bind:style="'opacity: ' + (selected.includes(file.id) ? '1' : '')">
                    <span style="background: var(--pdc-primary); color: white; font-size: 0.72rem; font-weight: 600; padding: 0.22rem 0.5rem; border-radius: 0.35rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: block; max-width: 100%; box-shadow: 0 1px 4px rgba(0,0,0,0.25);" x-text="file.name"></span>
                </div>

                <div x-show="file.sentAt" title="Nosūtīts klientam"
                    style="position: absolute; bottom: 0.5rem; left: 0.5rem; z-index: 11; height: 1.6rem; width: 1.6rem; border-radius: 9999px; background: #16a34a; color: white; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,0.35); box-shadow: 0 1px 4px rgba(0,0,0,0.3);"
                    x-cloak>
                    <svg x-show="file.sentAt" style="width: 0.9rem; height: 0.9rem;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                </div>

                @if($isReorderable)
                    <div x-show="index === 0" style="position: absolute; bottom: 0.5rem; left: 0.5rem; z-index: 10; background: var(--pdc-primary); color: white; font-size: 0.68rem; font-weight: 700; padding: 0.28rem 0.55rem; border-radius: 0.4rem; letter-spacing: 0.04em; box-shadow: 0 2px 8px rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.2); line-height: 1;">GALVENĀ</div>
                @endif
            </div>
        </template>
    </div>
    @else
    {{-- Row list mode (client attachments): filename, added date, filesize, send/remove actions --}}
    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
        <template x-for="(file, index) in files" :key="file.id">
            <div
                data-attach-row
                style="display: flex; align-items: center; gap: 0.75rem; border: 1px solid #e5e7eb; border-radius: 0.65rem; padding: 0.5rem 0.75rem; background: #ffffff; transition: border-color 0.15s ease;"
                :style="{ borderColor: file.sentAt ? '#16a34a' : '#e5e7eb' }"
            >
                <button
                    type="button"
                    x-on:click="file ? openLightbox(index) : null"
                    title="Atvērt"
                    style="flex: none; height: 3rem; width: 3rem; border-radius: 0.55rem; overflow: hidden; border: 1px solid #e5e7eb; background: #f9fafb; padding: 0; cursor: zoom-in; display: flex; align-items: center; justify-content: center;"
                >
                    <template x-if="isImage(file)">
                        <img
                            :src="thumbUrl(file)"
                            :alt="file.name"
                            loading="lazy"
                            x-on:error="onImgError($event, file)"
                            style="width: 100%; height: 100%; object-fit: cover; pointer-events: none;"
                        />
                    </template>
                    <template x-if="!isImage(file)">
                        <svg style="width: 1.5rem; height: 1.5rem; color: #6b7280; pointer-events: none;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                        </svg>
                    </template>
                </button>

                <div style="flex: 1 1 auto; min-width: 0; cursor: pointer;" x-on:click="openLightbox(index)">
                    <div style="font-size: 0.875rem; font-weight: 600; color: #111827; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" x-text="file.name"></div>
                    <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 0.4rem; margin-top: 0.15rem; font-size: 0.75rem; color: #6b7280;">
                        <span x-show="file.created" x-text="file.created"></span>
                        <span x-show="file.created && sizeLabel(file.size)">·</span>
                        <span x-show="sizeLabel(file.size)" x-text="sizeLabel(file.size)"></span>
                        @if($isRowMode)
                        <span x-bind:style="{ display: file.sentAt ? 'inline-flex' : 'none' }" class="fi-badge fi-color-success fi-color" style="display: inline-flex; align-items: center; gap: 0.25rem;" title="Nosūtīts klientam" x-cloak>
                            <svg style="width: 0.8rem; height: 0.8rem;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            <span>Nosūtīts klientam</span>
                        </span>
                        @endif
                    </div>
                </div>

                <div style="flex: none; display: flex; align-items: center; gap: 0.4rem;">
                    @if($isRowMode && $canSend)
                        <button
                            type="button"
                            x-bind:style="{ display: typeof file.id === 'number' ? 'inline-flex' : 'none' }"
                            x-on:click.stop="sendFile(file)"
                            title="Nosūtīt ar e-pastu"
                            style="display: inline-flex; flex-direction: row; flex-wrap: nowrap; align-items: center; gap: 0.35rem; white-space: nowrap; height: 2rem; padding: 0 0.7rem; border-radius: 0.5rem; background: var(--pdc-primary); color: white; font-size: 0.78rem; font-weight: 600; border: 1px solid var(--pdc-primary-darker, var(--pdc-primary)); cursor: pointer; line-height: 1.2;"
                        >
                            <svg style="width: 0.9rem; height: 0.9rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                            <span>Nosūtīt</span>
                        </button>
                    @endif
                    @if($isDeletable && ((!$isView) || $isSendable))
                        <button
                            type="button"
                            x-on:click.stop="removeFile(file.id)"
                            title="Dzēst"
                            style="display: inline-flex; align-items: center; justify-content: center; height: 2rem; width: 2rem; border-radius: 0.5rem; background: transparent; color: #6b7280; border: 1px solid #e5e7eb; cursor: pointer;"
                        >
                            <svg style="width: 1rem; height: 1rem;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                        </button>
                    @endif
                </div>
            </div>
        </template>
    </div>
    @if(!$isView)
        <div x-show="files.length > 0" style="margin-top: 0.5rem;">
            <button
                type="button"
                x-on:click="pickFiles()"
                class="fi-btn fi-size-sm fi-color fi-color-gray fi-outlined"
                style="cursor: pointer; display: inline-flex; align-items: center; gap: 0.35rem; flex-direction: row; white-space: nowrap;"
            >
                <svg style="width: 1rem; height: 1rem;" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 3.75a.75.75 0 01.75.75v6.75h6.75a.75.75 0 010 1.5h-6.75v6.75a.75.75 0 01-1.5 0v-6.75H4.5a.75.75 0 010-1.5h6.75V4.5a.75.75 0 01.75-.75z" clip-rule="evenodd"/></svg>
                <span>Pievienot failus</span>
            </button>
        </div>
    @endif
    @endif

    <template x-if="uploading.length > 0">
        <div style="margin-top: 1rem; padding: 1rem 1.25rem; border: 1px solid #bfdbfe; background: linear-gradient(135deg, #eff6ff 0%, #f0f9ff 100%); border-radius: 0.65rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div style="display: flex; align-items: center; gap: 0.65rem; margin-bottom: 0.65rem;">
                <svg style="width: 1.4rem; height: 1.4rem; color: #2563eb; animation: spin 1s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                </svg>
                <span style="font-size: 0.95rem; font-weight: 700; color: #1e40af;" x-text="'Augšupielādē ' + uploading.length + ' ' + (uploading.length === 1 ? 'failu' : 'failus') + '...'"></span>
            </div>
            <template x-for="u in uploading" :key="u.id">
                <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.45rem 0; border-top: 1px solid rgba(37,99,235,0.12);">
                    <span style="flex: 0 0 auto; max-width: 38%; font-size: 0.8rem; color: #1e3a8a; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" x-text="u.name"></span>
                    <div style="flex: 1; height: 0.55rem; background: #dbeafe; border-radius: 9999px; overflow: hidden;">
                        <div :style="{ width: u.progress + '%', height: '100%', background: 'linear-gradient(90deg, #3b82f6 0%, #2563eb 100%)', transition: 'width 0.15s ease' }"></div>
                    </div>
                    <span style="flex: 0 0 auto; font-size: 0.78rem; font-weight: 700; color: #2563eb; min-width: 3rem; text-align: right;" x-text="u.progress + '%'"></span>
                </div>
            </template>
        </div>
    </template>

    <div
        x-show="files.length === 0"
        style="text-align: center; padding: 2rem; border: 2px dashed #e5e7eb; border-radius: 0.75rem;"
    >
        <div style="display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; font-size: 0.875rem; color: #6b7280;">
            <svg style="max-width: 60px; width: 2.5rem; height: 2.5rem; flex: none; color: #9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <span>Nav pielikumu.</span>
        </div>
        @if(!$isView)
            <div style="margin-top: 0.75rem;">
                <button
                    type="button"
                    x-on:click="pickFiles()"
                    style="cursor: pointer; display: inline-flex; align-items: center; gap: 0.35rem; flex-direction: row; white-space: nowrap;"
                    class="fi-btn fi-size-sm fi-color fi-color-primary"
                >
                    <svg style="width: 1rem; height: 1rem;" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 3.75a.75.75 0 01.75.75v6.75h6.75a.75.75 0 010 1.5h-6.75v6.75a.75.75 0 01-1.5 0v-6.75H4.5a.75.75 0 010-1.5h6.75V4.5a.75.75 0 01.75-.75z" clip-rule="evenodd"/></svg>
                    <span>Pievienot failus</span>
                </button>
            </div>
        @endif
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
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <button type="button" x-on:click="closeEditor()" class="pdc-editor-btn pdc-editor-btn-secondary" :disabled="savingCrop">Atcelt</button>
                        <button type="button" x-on:click="saveEditor()" class="pdc-editor-btn pdc-editor-btn-primary" :disabled="savingCrop">Saglabāt</button>
                        <div x-show="savingCrop" style="display: flex; align-items: center; gap: 0.4rem; color: white; font-size: 0.82rem; font-weight: 600; padding-left: 0.25rem;">
                            <svg style="width: 1.1rem; height: 1.1rem; animation: spin 1s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                            </svg>
                            <span x-text="cropProgress + '%'"></span>
                        </div>
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
            <template x-if="isImage(lightboxFile || {})">
                <img :src="lightboxFile?.url" :alt="lightboxFile?.name" style="max-width: 90vw; max-height: 78vh; object-fit: contain; border-radius: 0.5rem; box-shadow: 0 8px 32px rgba(0,0,0,0.5);"/>
            </template>
            <template x-if="!isImage(lightboxFile || {})">
                <a x-bind:href="lightboxFile?.url" target="_blank"
                   style="display: flex; flex-direction: column; align-items: center; gap: 0.75rem; padding: 2.5rem 4rem; text-decoration: none; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.15); border-radius: 0.75rem; color: white; font-weight: 600;">
                    <svg style="width: 2.5rem; height: 2.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                    </svg>
                    <span style="font-size: 0.95rem;">Atvērt failu</span>
                </a>
            </template>
            <div style="display:flex; align-items:center; gap:0.75rem; flex-wrap: wrap; justify-content:center;">
                <span style="background: var(--pdc-primary); color:white; font-size:0.78rem; font-weight:600; padding:0.3rem 0.7rem; border-radius:9999px;" x-text="(lightboxIndex+1) + ' / ' + files.length"></span>
                <span style="background: var(--pdc-primary); color:white; font-size:0.78rem; font-weight:600; padding:0.3rem 0.7rem; border-radius:0.5rem; max-width: 60vw; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" x-text="lightboxFile?.name"></span>
                <a x-bind:href="lightboxFile?.url" target="_blank"
                   style="display: inline-flex; align-items: center; gap: 0.3rem; color: white; font-size: 0.8rem; font-weight: 600; padding: 0.3rem 0.7rem; border-radius: 9999px; background: var(--pdc-primary); text-decoration: none; border: 1px solid rgba(255,255,255,0.3);">
                    <svg style="width: 0.9rem; height: 0.9rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2m-16-5l8 8 8-8m-16 0V4h16v10"/></svg>
                    <span>Lejupielādēt</span>
                </a>
            </div>
        </div>
        </div>
    </template>

    @if($isRowMode && $canSend && $mailSendUrl)
        @include('filament.partials.attachment-send-popup', [
            'sendUrl' => $mailSendUrl,
            'clients' => $propertyClients,
            'defaultTo' => $mailDefaultTo,
            'baseWa' => $mailBaseWa,
        ])
    @endif
</div>
