@props(['canteens'])

<div
    x-data="{
        total: {{ $canteens->count() }},
        active: 0,
        timer: null,
        resumeTimer: null,
        isPaused: false,
        prefersReducedMotion: false,
        scrollRaf: null,

        init() {
            if (this.total <= 1) return;

            const mediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
            this.prefersReducedMotion = mediaQuery.matches;
            mediaQuery.addEventListener('change', (e) => {
                this.prefersReducedMotion = e.matches;
                if (this.prefersReducedMotion) {
                    this.stopAutoplay();
                } else {
                    this.startAutoplay();
                }
            });

            if (!this.prefersReducedMotion) {
                this.startAutoplay();
            }

            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    this.stopAutoplay();
                } else if (!this.prefersReducedMotion && !this.isPaused) {
                    this.startAutoplay();
                }
            });

            this.$nextTick(() => {
                this.updateActiveIndex();
            });
        },

        startAutoplay() {
            this.stopAutoplay();
            if (this.prefersReducedMotion || this.total <= 1) return;
            this.timer = setInterval(() => {
                this.next();
            }, 5000);
        },

        stopAutoplay() {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
        },

        pause() {
            this.isPaused = true;
            this.stopAutoplay();
            if (this.resumeTimer) {
                clearTimeout(this.resumeTimer);
                this.resumeTimer = null;
            }
        },

        resumeAfterDelay() {
            if (this.resumeTimer) {
                clearTimeout(this.resumeTimer);
            }
            this.resumeTimer = setTimeout(() => {
                this.isPaused = false;
                if (!document.hidden && !this.prefersReducedMotion) {
                    this.startAutoplay();
                }
            }, 6000);
        },

        onScroll() {
            if (this.scrollRaf) cancelAnimationFrame(this.scrollRaf);
            this.scrollRaf = requestAnimationFrame(() => {
                this.updateActiveIndex();
            });
        },

        updateActiveIndex() {
            const track = this.$refs.track;
            if (!track) return;
            const slides = track.querySelectorAll('[data-slide]');
            if (!slides.length) return;

            const padLeft = parseFloat(getComputedStyle(track).paddingLeft) || 0;
            const currentPos = track.scrollLeft + padLeft;
            let closestIndex = 0;
            let minDiff = Infinity;

            slides.forEach((slide, idx) => {
                const diff = Math.abs(slide.offsetLeft - currentPos);
                if (diff < minDiff) {
                    minDiff = diff;
                    closestIndex = idx;
                }
            });
            this.active = closestIndex;
        },

        goTo(index) {
            this.active = index;
            const track = this.$refs.track;
            if (!track) return;
            const slides = track.querySelectorAll('[data-slide]');
            if (slides[index]) {
                const slide = slides[index];
                const padLeft = parseFloat(getComputedStyle(track).paddingLeft) || 0;
                const targetScroll = slide.offsetLeft - track.offsetLeft - padLeft;
                const behavior = this.prefersReducedMotion ? 'auto' : 'smooth';
                track.scrollTo({
                    left: targetScroll,
                    behavior: behavior
                });
            }
        },

        next() {
            let nextIndex = (this.active + 1) % this.total;
            this.goTo(nextIndex);
        }
    }"
    class="w-full relative pt-2"
>
    <!-- Slides Track -->
    <div
        x-ref="track"
        @scroll.passive="onScroll"
        @mouseenter="pause"
        @mouseleave="resumeAfterDelay"
        @touchstart.passive="pause"
        @touchend.passive="resumeAfterDelay"
        @pointerdown="pause"
        @pointerup="resumeAfterDelay"
        class="flex gap-3 overflow-x-auto snap-x snap-mandatory no-scrollbar px-5 scroll-pl-5 scroll-smooth py-1"
        style="scrollbar-width: none; -ms-overflow-style: none;"
    >
        @foreach ($canteens as $index => $canteen)
            <div
                data-slide="{{ $index }}"
                class="flex-shrink-0 w-[calc(100%-24px)] snap-start"
            >
                <a href="{{ url('/kantin/' . $canteen->id) }}" class="block group h-full">
                    <div class="bg-surface rounded-[22px] border border-line overflow-hidden transition-colors hover:border-accent-sage h-full flex flex-col {{ !$canteen->is_open ? 'opacity-70' : '' }}">
                        <!-- Large photo -->
                        <div class="relative w-full h-48 bg-sage-tint overflow-hidden">
                            <img
                                src="{{ $canteen->photo_url }}"
                                alt="{{ $canteen->name }}"
                                class="w-full h-full object-cover transition duration-300 group-hover:scale-105 {{ !$canteen->is_open ? 'grayscale' : '' }}"
                                loading="lazy"
                            />
                        </div>

                        <!-- Deep-Green Label -->
                        <div class="bg-brand px-4 py-3 flex items-center justify-between">
                            <div class="min-w-0 pr-2">
                                <h2 class="text-[17px] font-bold font-heading text-on-brand truncate">{{ $canteen->name }}</h2>
                            </div>
                            <div class="flex-shrink-0">
                                @if ($canteen->is_open)
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[13px] font-semibold bg-sage-tint text-brand">
                                        <span class="w-1.5 h-1.5 rounded-full bg-accent-sage"></span>
                                        Buka
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-[13px] font-semibold bg-danger text-white">
                                        Tutup
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Card Footer -->
                        <div class="p-4 space-y-1.5 flex-1 flex flex-col justify-between">
                            <p class="text-[13px] text-muted line-clamp-1">
                                {{ $canteen->description ?? 'Menyediakan aneka hidangan lezat dan higienis.' }}
                            </p>
                            @if ($canteen->popular_menu_names)
                                <div class="pt-1 flex items-center gap-1 text-[13px]">
                                    <span class="text-accent-sage font-semibold flex-shrink-0">Populer:</span>
                                    <span class="text-muted truncate">{{ $canteen->popular_menu_names }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    <!-- Dots Indicator -->
    @if ($canteens->count() > 1)
        <div class="flex items-center justify-center gap-1.5 mt-3 px-5">
            @foreach ($canteens as $index => $canteen)
                <button
                    type="button"
                    @click="goTo({{ $index }}); pause(); resumeAfterDelay();"
                    :class="active === {{ $index }} ? 'w-5 bg-brand' : 'w-1.5 bg-line hover:bg-accent-sage'"
                    class="h-1.5 rounded-full transition-all duration-300 focus:outline-none"
                    aria-label="Ke kantin {{ $canteen->name }}"
                ></button>
            @endforeach
        </div>
    @endif
</div>
