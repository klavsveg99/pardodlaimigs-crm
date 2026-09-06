@php
    $statePath = $getStatePath();
    $currentPath = $getState();
    $currentUrl = $currentPath ? \Illuminate\Support\Facades\Storage::disk('public')->url($currentPath) : null;
    $uid = 'avatar-' . str_replace('.', '-', $statePath);
    $uploadUrl = route('filament.admin.avatar.upload');
@endphp

<script type="application/json" id="{{ $uid }}-data">{!! json_encode(['path' => $currentPath, 'url' => $currentUrl]) !!}</script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" />

<style>
    .avatar-editor-preview {
        width: 180px;
        height: 180px;
        border-radius: 9999px;
        overflow: hidden;
        border: 3px solid #e5e7eb;
        background: #f9fafb;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .avatar-editor-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .avatar-editor-placeholder {
        color: #9ca3af;
        font-size: 0.85rem;
        text-align: center;
        padding: 1rem;
    }
    .pdc-editor-modal {
        position: fixed;
        inset: 0;
        z-index: 100000;
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
    @keyframes spin { to { transform: rotate(360deg); } }
</style>

<div
    x-data="{
        path: null,
        url: null,
        uploadUrl: null,
        csrfToken: null,
        cropper: null,
        editorOpen: false,
        editorAspectRatio: 1,
        scaleX: 1,
        scaleY: 1,
        uploading: false,
        uploadProgress: 0,
        saving: false,
        saveProgress: 0,
        init() {
            try {
                const el = document.getElementById('{{ $uid }}-data');
                const raw = el ? el.textContent : null;
                let data = null;
                if (raw) { try { data = JSON.parse(raw); } catch(e) { data = null; } }
                this.path = (data && data.path) ? data.path : null;
                this.url = (data && data.url) ? data.url : null;
            } catch(e) { this.path = null; this.url = null; }
            this.uploadUrl = '{{ $uploadUrl }}';
            this.csrfToken = document.querySelector('meta[name=\'csrf-token\']')?.content || '';
            if (!window.Cropper && !document.querySelector('script[data-cropper]')) {
                const s = document.createElement('script');
                s.src = 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js';
                s.setAttribute('data-cropper','1');
                document.head.appendChild(s);
            }
        },
        sync() { $wire.set('{{ $statePath }}', this.path, false); },
        async handleUpload(e) {
            const input = e.target;
            const file = input.files?.[0];
            if (!file) return;
            this.uploading = true;
            this.uploadProgress = 0;
            const xhr = new XMLHttpRequest();
            xhr.open('POST', this.uploadUrl, true);
            xhr.setRequestHeader('X-CSRF-TOKEN', this.csrfToken);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.upload.addEventListener('progress', (ev) => {
                if (ev.lengthComputable) {
                    this.uploadProgress = Math.round(ev.loaded / ev.total * 95);
                }
            });
            xhr.addEventListener('load', () => {
                try {
                    const r = JSON.parse(xhr.responseText);
                    if (r.path) {
                        this.path = r.path;
                        this.url = r.url + (r.url.includes('?') ? '&' : '?') + 'v=' + Date.now();
                        this.sync();
                        this.$nextTick(() => this.openEditor());
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
                this.uploading = false;
                this.uploadProgress = 0;
            });
            const formData = new FormData();
            formData.append('file', file);
            formData.append('_token', this.csrfToken);
            xhr.send(formData);
            input.value = '';
        },
        openEditor() {
            if (!this.url) {
                alert('Vispirms augšupielādējiet attēlu.');
                return;
            }
            this.editorOpen = true;
            document.body.style.overflow = 'hidden';
            this.$nextTick(() => this.initCropper());
        },
        closeEditor() {
            this.editorOpen = false;
            document.body.style.overflow = '';
            this.destroyCropper();
        },
        initCropper() {
            this.destroyCropper();
            const img = this.$refs.editorImage;
            if (!img) return;
            const tryInit = () => {
                if (!window.Cropper) {
                    setTimeout(tryInit, 200);
                    return;
                }
                const build = () => {
                    if (this.cropper) return;
                    this.cropper = new window.Cropper(img, {
                        viewMode: 1,
                        autoCropArea: 1,
                        aspectRatio: this.editorAspectRatio,
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
                img.onload = build;
                img.onerror = () => {
                    console.error('Avatar editor: image failed to load', this.url);
                    alert('Neizdevās ielādēt attēlu redaktorā.');
                };
                img.src = this.url;
                if (img.complete && img.naturalWidth > 0) {
                    build();
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
            if (!this.cropper || this.saving) return;
            const canvas = this.cropper.getCroppedCanvas({
                maxWidth: 1000,
                maxHeight: 1000,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high',
                fillColor: '#fff',
            });
            if (!canvas) {
                alert('Neizdevās apgriezt attēlu.');
                return;
            }
            this.saving = true;
            this.saveProgress = 0;
            canvas.toBlob((blob) => {
                if (!blob) {
                    this.saving = false;
                    alert('Neizdevās saglabāt attēlu.');
                    return;
                }
                const fileName = 'avatar-' + Date.now() + '.jpg';
                const file = new File([blob], fileName, { type: 'image/jpeg' });
                const xhr = new XMLHttpRequest();
                xhr.open('POST', this.uploadUrl, true);
                xhr.setRequestHeader('X-CSRF-TOKEN', this.csrfToken);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.upload.addEventListener('progress', (ev) => {
                    if (ev.lengthComputable) {
                        this.saveProgress = Math.round(ev.loaded / ev.total * 95);
                    }
                });
                xhr.addEventListener('load', () => {
                    try {
                        const r = JSON.parse(xhr.responseText);
                        if (r.path) {
                            this.path = r.path;
                            this.url = r.url + (r.url.includes('?') ? '&' : '?') + 'v=' + Date.now();
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
                    this.saving = false;
                    this.saveProgress = 0;
                });
                const formData = new FormData();
                formData.append('file', file);
                formData.append('_token', this.csrfToken);
                xhr.send(formData);
            }, 'image/jpeg', 0.92);
        },
        removeAvatar() {
            if (!confirm('Dzēst foto?')) return;
            this.path = null;
            this.url = null;
            this.sync();
        }
    }"
    wire:ignore.self
    x-on:keydown.escape.window="if(editorOpen) closeEditor()"
>

    <div style="display: flex; flex-direction: column; gap: 1rem; align-items: flex-start;">
        <div class="avatar-editor-preview">
            <template x-if="url">
                <img :src="url" alt="Avatars" />
            </template>
            <template x-if="!url">
                <div class="avatar-editor-placeholder">
                    <svg style="width: 3rem; height: 3rem; margin: 0 auto 0.5rem; opacity: 0.5;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                    <div>Nav foto</div>
                </div>
            </template>
        </div>

        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <label style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 0.9rem; border-radius: 0.5rem; background: var(--pdc-primary); color: white; font-size: 0.85rem; font-weight: 600; cursor: pointer; border: 1px solid var(--pdc-primary);">
                <svg style="width: 1rem; height: 1rem;" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 3.75a.75.75 0 01.75.75v6.75h6.75a.75.75 0 010 1.5h-6.75v6.75a.75.75 0 01-1.5 0v-6.75H4.5a.75.75 0 010-1.5h6.75V4.5a.75.75 0 01.75-.75z" clip-rule="evenodd"/></svg>
                <span x-text="url ? 'Nomainīt foto' : 'Augšupielādēt foto'"></span>
                <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" style="display: none;" x-on:change="handleUpload($event)" />
            </label>

            <template x-if="url">
                <button type="button" x-on:click="openEditor()" style="display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.5rem 0.85rem; border-radius: 0.5rem; background: #1f2937; color: white; border: 1px solid rgba(255,255,255,0.15); font-size: 0.8rem; font-weight: 600; cursor: pointer;">
                    <svg style="width: 0.9rem; height: 0.9rem;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.83 15.83a2 2 0 01-1.415.586H9a1 1 0 01-1-1v-1.415a2 2 0 01.586-1.414l9-9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 5l4 4"/></svg>
                    <span>Rediģēt</span>
                </button>
            </template>

            <template x-if="url">
                <button type="button" x-on:click="removeAvatar()" style="display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.5rem 0.85rem; border-radius: 0.5rem; background: transparent; color: #cf2e2e; border: 1px solid #cf2e2e; font-size: 0.8rem; font-weight: 600; cursor: pointer;">
                    <svg style="width: 0.9rem; height: 0.9rem;" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M16.5 4.478v.227a48.816 48.816 0 0 1 3.878.512.75.75 0 1 1-.256 1.478l-.209-.035-1.005 13.07a3 3 0 0 1-2.991 2.77H8.084a3 3 0 0 1-2.991-2.77L4.087 6.66l-.209.035a.75.75 0 0 1-.256-1.478A48.567 48.567 0 0 1 7.5 4.705v-.227c0-1.564 1.213-2.9 2.816-2.951a52.662 52.662 0 0 1 3.369 0c1.603.051 2.815 1.387 2.815 2.951Zm-6.136-1.452a51.196 51.196 0 0 1 3.273 0C14.39 3.05 15 3.684 15 4.478v.113a49.488 49.488 0 0 0-6 0v-.113c0-.794.609-1.428 1.364-1.452Zm-.355 5.945a.75.75 0 1 0-1.5.058l.347 9a.75.75 0 1 0 1.499-.058l-.346-9Zm5.48.058a.75.75 0 1 0-1.498-.058l-.347 9a.75.75 0 0 0 1.5.058l.345-9Z" clip-rule="evenodd"/></svg>
                    <span>Dzēst</span>
                </button>
            </template>
        </div>
        <p style="font-size: 0.75rem; color: #6b7280;">Kvadrātveida foto — 1:1, 1000×1000px, max 5MB. Izmanto redaktoru, lai apgrieztu un apvērstu.</p>
        <template x-if="uploading">
            <div style="width: 100%; padding: 0.7rem 0.9rem; border: 1px solid #bfdbfe; background: linear-gradient(135deg, #eff6ff 0%, #f0f9ff 100%); border-radius: 0.55rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; gap: 0.55rem; margin-bottom: 0.45rem;">
                    <svg style="width: 1.05rem; height: 1.05rem; color: #2563eb; animation: spin 1s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                        <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                    </svg>
                    <span style="font-size: 0.82rem; font-weight: 700; color: #1e40af;">Augšupielādē foto...</span>
                    <span style="margin-left: auto; font-size: 0.82rem; font-weight: 700; color: #2563eb;" x-text="uploadProgress + '%'"></span>
                </div>
                <div style="height: 0.5rem; background: #dbeafe; border-radius: 9999px; overflow: hidden;">
                    <div :style="{ width: uploadProgress + '%', height: '100%', background: 'linear-gradient(90deg, #3b82f6 0%, #2563eb 100%)', transition: 'width 0.15s ease' }"></div>
                </div>
            </div>
        </template>
    </div>

    <!-- Editor Modal -->
    <template x-if="editorOpen">
        <div style="position: fixed; inset: 0; z-index: 100000; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.88); padding: 1rem;" x-transition.opacity x-on:click.self="closeEditor()">
            <div class="pdc-editor-panel" x-on:click.stop>
                <div class="pdc-editor-header">
                    <span class="pdc-editor-title">Rediģēt foto</span>
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
                        <button type="button" x-on:click="setAspectRatio(1)" :class="editorAspectRatio === 1 ? 'active' : ''" class="pdc-editor-btn pdc-editor-btn-ghost">1:1</button>
                        <button type="button" x-on:click="setAspectRatio(4/3)" :class="editorAspectRatio === 4/3 ? 'active' : ''" class="pdc-editor-btn pdc-editor-btn-ghost">4:3</button>
                        <button type="button" x-on:click="setAspectRatio(16/9)" :class="editorAspectRatio === 16/9 ? 'active' : ''" class="pdc-editor-btn pdc-editor-btn-ghost">16:9</button>
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
                        <button type="button" x-on:click="closeEditor()" class="pdc-editor-btn pdc-editor-btn-secondary" :disabled="saving">Atcelt</button>
                        <button type="button" x-on:click="saveEditor()" class="pdc-editor-btn pdc-editor-btn-primary" :disabled="saving">Saglabāt</button>
                        <template x-if="saving">
                            <div style="display: flex; align-items: center; gap: 0.4rem; color: white; font-size: 0.82rem; font-weight: 600; padding-left: 0.25rem;">
                                <svg style="width: 1.1rem; height: 1.1rem; animation: spin 1s linear infinite;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                    <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                                </svg>
                                <span x-text="saveProgress + '%'"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
