@php
    $date = fn ($value) => $value?->locale('lv')->translatedFormat('d.m.Y');
@endphp

<section class="pdc-kb-courses">
    <style>
        .pdc-kb-courses { display: grid; gap: 0.85rem; margin-bottom: 0.5rem; }
        .pdc-kb-courses-head { display: flex; align-items: baseline; justify-content: space-between; gap: 1rem; }
        .pdc-kb-courses-title { font-size: 1.05rem; font-weight: 700; color: #111827; margin: 0; }
        .pdc-kb-courses-sub { font-size: 0.8rem; color: #6b7280; }
        .pdc-kb-course-list { display: grid; gap: 0.6rem; }
        .pdc-kb-course {
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            background: #ffffff;
            overflow: hidden;
        }
        .pdc-kb-course[open] { border-color: #cbd5d4; }
        .pdc-kb-course > summary {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.8rem 0.9rem;
            cursor: pointer;
            list-style: none;
        }
        .pdc-kb-course > summary::-webkit-details-marker { display: none; }
        .pdc-kb-course-main { flex: 1 1 auto; min-width: 0; display: grid; gap: 0.2rem; }
        .pdc-kb-course-name { font-size: 0.92rem; font-weight: 700; color: #111827; }
        .pdc-kb-course-desc { font-size: 0.78rem; color: #6b7280; overflow-wrap: anywhere; }
        .pdc-kb-course-meta { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; font-size: 0.75rem; color: #6b7280; }
        .pdc-kb-course-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.15rem 0.5rem;
            border: 1px solid #e5e7eb;
            border-radius: 9999px;
            font-size: 0.68rem;
            font-weight: 700;
            color: #4b5563;
            white-space: nowrap;
        }
        .pdc-kb-course-badge--done { border-color: #a7f3d0; background: #ecfdf5; color: #047857; }
        .pdc-kb-course-badge--overdue { border-color: #fecaca; background: #fef2f2; color: #b91c1c; }
        .pdc-kb-course-progress { width: 9rem; max-width: 34vw; display: grid; gap: 0.25rem; }
        .pdc-kb-course-chevron { color: #9ca3af; transition: transform 0.15s ease; }
        .pdc-kb-course[open] .pdc-kb-course-chevron { transform: rotate(180deg); }
        .pdc-kb-course-bar { height: 0.4rem; border-radius: 9999px; background: #e5e7eb; overflow: hidden; }
        .pdc-kb-course-bar > span { display: block; height: 100%; border-radius: inherit; background: var(--pdc-primary, #285854); }
        .pdc-kb-course-progress-label { font-size: 0.68rem; color: #6b7280; text-align: right; }
        .pdc-kb-course-body { display: grid; gap: 1rem; padding: 0.9rem; border-top: 1px solid #f3f4f6; }
        .pdc-kb-course-articles { display: grid; gap: 0.35rem; margin: 0; padding: 0; list-style: none; }
        .pdc-kb-course-article { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; padding: 0.45rem 0; border-bottom: 1px solid #f3f4f6; }
        .pdc-kb-course-article:last-child { border-bottom: 0; }
        .pdc-kb-course-article a { font-size: 0.85rem; font-weight: 600; color: var(--pdc-primary, #285854); text-decoration: none; }
        .pdc-kb-course-article a:hover { text-decoration: underline; }
        .pdc-kb-course-article-state { font-size: 0.72rem; font-weight: 700; color: #6b7280; white-space: nowrap; }
        .pdc-kb-course-article-state--done { color: #047857; }
        .pdc-kb-course-section-title { font-size: 0.8rem; font-weight: 700; color: #374151; margin: 0; }
        .pdc-kb-courses-empty { font-size: 0.85rem; color: #6b7280; margin: 0; }
        html.dark .pdc-kb-courses-title { color: #f3f4f6; }
        html.dark .pdc-kb-courses-sub { color: #9ca3af; }
        html.dark .pdc-kb-course { border-color: #27303a; background: #0f151b; }
        html.dark .pdc-kb-course[open] { border-color: #3b4a56; }
        html.dark .pdc-kb-course-name { color: #f3f4f6; }
        html.dark .pdc-kb-course-desc, html.dark .pdc-kb-course-meta, html.dark .pdc-kb-course-progress-label { color: #9ca3af; }
        html.dark .pdc-kb-course-badge { border-color: #374151; color: #d1d5db; }
        html.dark .pdc-kb-course-badge--done { border-color: #064e3b; background: #052e25; color: #6ee7b7; }
        html.dark .pdc-kb-course-badge--overdue { border-color: #7f1d1d; background: #2a0f0f; color: #fca5a5; }
        html.dark .pdc-kb-course-bar { background: #27303a; }
        html.dark .pdc-kb-course-body { border-color: #1f2831; }
        html.dark .pdc-kb-course-article { border-color: #1f2831; }
        html.dark .pdc-kb-course-article a { color: #98bcb8; }
        html.dark .pdc-kb-course-article-state { color: #9ca3af; }
        html.dark .pdc-kb-course-article-state--done { color: #6ee7b7; }
        html.dark .pdc-kb-course-section-title { color: #e5e7eb; }
        html.dark .pdc-kb-courses-empty { color: #9ca3af; }
        @media (max-width: 639px) {
            .pdc-kb-course > summary { align-items: flex-start; flex-wrap: wrap; }
            .pdc-kb-course-progress { width: 100%; max-width: none; }
            .pdc-kb-course-progress-label { text-align: left; }
        }
    </style>

    <div class="pdc-kb-courses-head">
        <h2 class="pdc-kb-courses-title">Kursi</h2>
        <span class="pdc-kb-courses-sub">Tev piešķirtie kursi</span>
    </div>

    @if ($courses === [])
        <p class="pdc-kb-courses-empty">Tev vēl nav piešķirts neviens kurss.</p>
    @else
        <div class="pdc-kb-course-list">
            @foreach ($courses as $course)
                <details class="pdc-kb-course" wire:key="kb-course-{{ $course['assignment']->id }}">
                    <summary>
                        <div class="pdc-kb-course-main">
                            <span class="pdc-kb-course-name">{{ $course['category']->name }}</span>
                            @if (filled($course['category']->description))
                                <span class="pdc-kb-course-desc">{{ $course['category']->description }}</span>
                            @endif
                            <span class="pdc-kb-course-meta">
                                <span>{{ $course['progress']['completed'] }}/{{ $course['progress']['total'] }} materiāli</span>
                                @if ($course['is_overdue'])
                                    <span class="pdc-kb-course-badge pdc-kb-course-badge--overdue">Termiņš nokavēts · {{ $date($course['due_at']) }}</span>
                                @elseif ($course['due_at'])
                                    <span class="pdc-kb-course-badge">Termiņš {{ $date($course['due_at']) }}</span>
                                @endif
                                @if ($course['completed'])
                                    <span class="pdc-kb-course-badge pdc-kb-course-badge--done">Pabeigts</span>
                                @endif
                                @if ($course['comment_count'] > 0)
                                    <span>{{ $course['comment_count'] }} komentāri</span>
                                @endif
                            </span>
                        </div>
                        <div class="pdc-kb-course-progress">
                            <div class="pdc-kb-course-bar"><span style="width: {{ $course['progress']['percent'] }}%"></span></div>
                            <span class="pdc-kb-course-progress-label">{{ $course['progress']['percent'] }}%</span>
                        </div>
                        <i class="fa-solid fa-chevron-down pdc-kb-course-chevron" aria-hidden="true"></i>
                    </summary>

                    <div class="pdc-kb-course-body">
                        <div>
                            <h3 class="pdc-kb-course-section-title">Materiāli</h3>
                            <ul class="pdc-kb-course-articles">
                                @foreach ($course['articles'] as $article)
                                    <li class="pdc-kb-course-article">
                                        <a href="{{ $article['url'] }}">{{ $article['title'] }}</a>
                                        <span class="pdc-kb-course-article-state {{ $article['completed'] ? 'pdc-kb-course-article-state--done' : '' }}">
                                            {{ $article['completed'] ? 'Pabeigts' : 'Jāizpilda' }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        @if ($course['completed'])
                            @livewire(
                                \App\Livewire\KnowledgeCourseRating::class,
                                ['assignmentId' => $course['assignment']->id],
                                key('kb-course-rating-'.$course['assignment']->id)
                            )
                        @endif

                        <div>
                            <h3 class="pdc-kb-course-section-title" style="margin-bottom: 0.5rem;">Komentāri par kursu</h3>
                            @livewire(
                                \App\Livewire\KnowledgeComments::class,
                                ['categoryId' => $course['category']->id],
                                key('kb-course-comments-'.$course['category']->id)
                            )
                        </div>
                    </div>
                </details>
            @endforeach
        </div>
    @endif
</section>
