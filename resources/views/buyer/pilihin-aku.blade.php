<x-layouts.buyer title="Pilihin Aku - Kantin Kampus">
    <x-page-header title="Pilihin Aku" subtitle="Biar kami yang pilihin menu pas buatmu" backUrl="{{ url('/') }}" />

    <div
        x-data="{
            hasSearched: {{ $hasSearched ? 'true' : 'false' }},
            budget: {{ $budget }},
            customBudget: '{{ in_array($budget, [10000, 15000, 20000, 25000]) ? '' : $budget }}',
            mood: '{{ $mood }}',
            conflictModalOpen: false,
            conflictCurrentCanteen: '',
            conflictNewCanteen: '',
            pendingMenuIds: [],
            pendingLogId: null,
            submitting: false,

            selectBudget(amount) {
                this.budget = amount;
                this.customBudget = '';
            },

            onCustomBudgetInput(val) {
                const num = parseInt(val, 10);
                if (!isNaN(num)) {
                    this.budget = num;
                }
            },

            async orderCombo(menuIds, logId, force = false) {
                if (this.submitting) return;
                this.submitting = true;

                try {
                    const res = await fetch('{{ route('pilihin-aku.order') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            menu_ids: menuIds,
                            log_id: logId,
                            force: force
                        })
                    });

                    const data = await res.json();

                    if (data.conflict) {
                        this.pendingMenuIds = menuIds;
                        this.pendingLogId = logId;
                        this.conflictCurrentCanteen = data.current_canteen || 'Kantin lain';
                        this.conflictNewCanteen = data.new_canteen || 'Kantin baru';
                        this.conflictModalOpen = true;
                    } else if (data.success) {
                        window.location.href = data.redirect || '{{ route('cart.index') }}';
                    } else {
                        alert(data.message || 'Terjadi kesalahan saat menambahkan ke keranjang.');
                    }
                } catch (e) {
                    console.error('Order error:', e);
                } finally {
                    this.submitting = false;
                }
            },

            confirmReplaceCart() {
                this.conflictModalOpen = false;
                this.orderCombo(this.pendingMenuIds, this.pendingLogId, true);
            }
        }"
        class="pb-16"
    >
        @if ($errors->any())
            <div class="m-5 p-3.5 bg-danger/10 border border-danger/20 text-danger text-[13px] rounded-2xl">
                @foreach ($errors->all() as $err)
                    <p>{{ $err }}</p>
                @endforeach
            </div>
        @endif

        {{-- 1. QUESTIONNAIRE VIEW (Initial or when toggled) --}}
        <div x-show="!hasSearched" class="px-5 pt-4 space-y-6">
            <form action="{{ route('pilihin-aku.index') }}" method="GET" class="space-y-6">
                <!-- Question 1: Budget -->
                <div class="bg-surface border border-line rounded-3xl p-5 space-y-4 shadow-xs">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-sage-tint text-brand text-xs font-bold flex items-center justify-center">1</span>
                            <h2 class="text-[16px] font-bold font-heading text-text-main">Budget kamu berapa?</h2>
                        </div>
                        <p class="text-[12px] text-muted mt-1 ml-8">Pilih kisaran budget atau ketik sendiri (min. Rp5.000)</p>
                    </div>

                    <!-- Preset Chips -->
                    <div class="grid grid-cols-2 gap-2.5 pt-1">
                        @foreach ([10000, 15000, 20000, 25000] as $amount)
                            <button
                                type="button"
                                @click="selectBudget({{ $amount }})"
                                :class="(budget === {{ $amount }} && !customBudget) ? 'bg-brand text-on-brand shadow-xs' : 'bg-page text-text-main border border-line hover:border-accent-sage'"
                                class="py-3 px-4 rounded-2xl text-[14px] font-bold transition-all text-center"
                            >
                                @rupiah($amount)
                            </button>
                        @endforeach
                    </div>

                    <!-- Custom Amount Input -->
                    <div class="pt-1">
                        <label for="custom_budget" class="block text-[11px] font-semibold text-muted uppercase tracking-wider mb-1.5">
                            Atau ketik budget khusus:
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-xs font-bold text-muted pointer-events-none">
                                Rp
                            </span>
                            <input
                                type="number"
                                id="custom_budget"
                                x-model="customBudget"
                                @input="onCustomBudgetInput($event.target.value)"
                                placeholder="mis. 18000"
                                min="5000"
                                step="1000"
                                class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-line bg-page text-text-main text-[14px] font-semibold placeholder:text-muted focus:border-accent-sage focus:ring-1 focus:ring-accent-sage focus:outline-none transition"
                            />
                        </div>
                    </div>

                    <!-- Hidden input to submit actual budget -->
                    <input type="hidden" name="budget" :value="budget" />
                </div>

                <!-- Question 2: Mood -->
                <div class="bg-surface border border-line rounded-3xl p-5 space-y-4 shadow-xs">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-sage-tint text-brand text-xs font-bold flex items-center justify-center">2</span>
                            <h2 class="text-[16px] font-bold font-heading text-text-main">Lagi pengen yang gimana?</h2>
                        </div>
                        <p class="text-[12px] text-muted mt-1 ml-8">Sesuaikan dengan selera atau mood makanmu saat ini</p>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5 pt-1">
                        @php
                            $moodOptions = [
                                ['value' => 'segar', 'label' => 'Segar', 'desc' => 'Es teh, jus, buah'],
                                ['value' => 'hangat', 'label' => 'Hangat', 'desc' => 'Soto, bakso, kuah'],
                                ['value' => 'pedas', 'label' => 'Pedas', 'desc' => 'Geprek, sambal, seblak'],
                                ['value' => 'bebas', 'label' => 'Bebas', 'desc' => 'Apa aja yang enak'],
                            ];
                        @endphp

                        @foreach ($moodOptions as $opt)
                            <button
                                type="button"
                                @click="mood = '{{ $opt['value'] }}'"
                                :class="mood === '{{ $opt['value'] }}' ? 'bg-brand text-on-brand shadow-xs' : 'bg-page text-text-main border border-line hover:border-accent-sage'"
                                class="p-3 rounded-2xl transition-all text-left flex flex-col justify-between"
                            >
                                <span class="text-[14px] font-bold">{{ $opt['label'] }}</span>
                                <span class="text-[11px] mt-0.5 opacity-80" :class="mood === '{{ $opt['value'] }}' ? 'text-on-brand' : 'text-muted'">{{ $opt['desc'] }}</span>
                            </button>
                        @endforeach
                    </div>

                    <input type="hidden" name="mood" :value="mood" />
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    class="w-full h-[52px] rounded-full bg-brand text-on-brand font-bold text-[15px] shadow-md hover:opacity-90 active:scale-[0.98] transition flex items-center justify-center gap-2"
                >
                    <svg class="w-5 h-5 text-on-brand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <span>Cariin</span>
                </button>
            </form>
        </div>

        {{-- 2. RESULTS VIEW --}}
        @if ($hasSearched)
            <div x-show="hasSearched" class="px-5 pt-4 space-y-4">
                <!-- Summary bar & Change answer button -->
                <div class="flex items-center justify-between bg-surface border border-line rounded-2xl p-3 px-4 shadow-xs">
                    <div class="text-[13px] text-text-main">
                        <span class="text-muted">Budget:</span> <span class="font-bold">@rupiah($budget)</span>
                        <span class="mx-1 text-muted">•</span>
                        <span class="text-muted">Mood:</span> <span class="font-bold capitalize">{{ $mood }}</span>
                    </div>
                    <button
                        type="button"
                        @click="hasSearched = false"
                        class="text-[12px] font-bold text-accent-sage hover:underline"
                    >
                        Ganti
                    </button>
                </div>

                @if (empty($suggestions))
                    <!-- Empty Result State -->
                    <div class="text-center py-12 px-6 bg-surface rounded-3xl border border-line space-y-4 shadow-xs my-4">
                        <div class="w-16 h-16 mx-auto rounded-full bg-sage-tint flex items-center justify-center text-brand">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="M16 16s-1.5-2-4-2-4 2-4 2"/>
                                <line x1="9" y1="9" x2="9.01" y2="9"/>
                                <line x1="15" y1="9" x2="15.01" y2="9"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-[16px] font-bold font-heading text-text-main">Belum ada paket yang pas</h3>
                            <p class="text-[13px] text-muted mt-1.5 max-w-xs mx-auto leading-relaxed">
                                Coba naikkan budget atau pilih Bebas untuk melihat lebih banyak pilihan menu.
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="hasSearched = false"
                            class="inline-flex items-center justify-center px-6 h-[44px] bg-brand text-on-brand text-[13px] font-bold rounded-full hover:opacity-90 transition active:scale-95 shadow-sm"
                        >
                            Ubah Jawaban
                        </button>
                    </div>
                @else
                    <!-- Suggestions List (Up to 3 cards) -->
                    <div class="space-y-4 pt-1">
                        <div class="flex items-center justify-between">
                            <span class="text-xs uppercase font-bold text-muted tracking-wider">Rekomendasi Paket</span>
                            <span class="text-xs font-semibold text-accent-sage">{{ count($suggestions) }} pilihan pas</span>
                        </div>

                        @foreach ($suggestions as $index => $combo)
                            @php
                                $isFirst = ($index === 0);
                                $main = $combo['main'];
                                $drink = $combo['drink'];
                                $canteen = $combo['canteen'];
                                $totalPrice = $combo['total_price'];
                                $menuIds = $drink ? [$main->id, $drink->id] : [$main->id];
                            @endphp

                            <div class="bg-surface rounded-3xl border border-line p-5 shadow-xs space-y-4 transition-colors hover:border-accent-sage">
                                <!-- Canteen Header -->
                                <div class="flex items-center justify-between border-b border-line pb-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-sage-tint flex items-center justify-center text-brand text-xs font-bold">
                                            {{ $index + 1 }}
                                        </div>
                                        <div>
                                            <h3 class="text-[14px] font-bold text-text-main leading-tight">{{ $canteen->name }}</h3>
                                            @if ($combo['is_combo'])
                                                <span class="text-[11px] text-muted">Paket Makanan + Minuman</span>
                                            @else
                                                <span class="text-[11px] text-accent-sage font-medium">Menu Utama Pilihan</span>
                                            @endif
                                        </div>
                                    </div>

                                    @if ($isFirst)
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-butter/20 text-text-main border border-butter/40">
                                            Paling Pas
                                        </span>
                                    @endif
                                </div>

                                <!-- Items List -->
                                <div class="space-y-3">
                                    <!-- Main Course -->
                                    <div class="flex items-center gap-3">
                                        <div class="w-14 h-14 rounded-2xl overflow-hidden bg-sage-tint flex-shrink-0 border border-line">
                                            <img
                                                src="{{ $main->photo_url }}"
                                                alt="{{ $main->name }}"
                                                class="w-full h-full object-cover"
                                                loading="lazy"
                                            />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-start justify-between gap-2">
                                                <h4 class="text-[13px] font-bold text-text-main truncate">{{ $main->name }}</h4>
                                                <span class="text-[13px] font-semibold text-text-main whitespace-nowrap">@rupiah($main->price)</span>
                                            </div>
                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                <span class="text-[11px] text-muted">Makanan berat</span>
                                                @if (!empty($main->flavor_tags))
                                                    <span class="text-muted text-[10px]">•</span>
                                                    @foreach ($main->flavor_tags as $t)
                                                        <span class="text-[10px] text-accent-sage font-medium uppercase">{{ $t }}</span>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Drink (if combo) -->
                                    @if ($drink)
                                        <div class="flex items-center gap-3 pt-1 border-t border-line/60">
                                            <div class="w-14 h-14 rounded-2xl overflow-hidden bg-sage-tint flex-shrink-0 border border-line">
                                                <img
                                                    src="{{ $drink->photo_url }}"
                                                    alt="{{ $drink->name }}"
                                                    class="w-full h-full object-cover"
                                                    loading="lazy"
                                                />
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-start justify-between gap-2">
                                                    <h4 class="text-[13px] font-bold text-text-main truncate">{{ $drink->name }}</h4>
                                                    <span class="text-[13px] font-semibold text-text-main whitespace-nowrap">@rupiah($drink->price)</span>
                                                </div>
                                                <div class="flex items-center gap-1.5 mt-0.5">
                                                    <span class="text-[11px] text-muted">Minuman</span>
                                                    @if (!empty($drink->flavor_tags))
                                                        <span class="text-muted text-[10px]">•</span>
                                                        @foreach ($drink->flavor_tags as $t)
                                                            <span class="text-[10px] text-accent-sage font-medium uppercase">{{ $t }}</span>
                                                        @endforeach
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <!-- Card Footer: Total + Order Button -->
                                <div class="pt-3 border-t border-line flex items-center justify-between gap-3">
                                    <div>
                                        <span class="text-[11px] text-muted block">Total Biaya</span>
                                        <span class="text-[17px] font-extrabold text-text-main leading-tight">
                                            Total @rupiah($totalPrice)
                                        </span>
                                    </div>

                                    <!-- Button: Only first card is filled primary, others secondary -->
                                    <button
                                        type="button"
                                        @click="orderCombo({{ json_encode($menuIds) }}, {{ $logId ?? 'null' }})"
                                        :disabled="submitting"
                                        class="px-5 h-[44px] rounded-full text-[13px] font-bold transition flex items-center gap-1.5 active:scale-95 disabled:opacity-50 {{ $isFirst ? 'bg-brand text-on-brand shadow-sm hover:opacity-90' : 'bg-surface border border-line text-text-main hover:bg-page hover:border-accent-sage' }}"
                                    >
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
                                            <line x1="3" y1="6" x2="21" y2="6"/>
                                            <path d="M16 10a4 4 0 01-8 0"/>
                                        </svg>
                                        <span>Pesan ini</span>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Below: Secondary Button "Acak lagi" -->
                    <div class="pt-2">
                        <a
                            href="{{ route('pilihin-aku.index', ['budget' => $budget, 'mood' => $mood, 'r' => rand(1000, 9999)]) }}"
                            class="w-full h-[48px] rounded-full border border-line bg-surface hover:bg-page text-text-main font-bold text-[14px] transition flex items-center justify-center gap-2 active:scale-98 shadow-xs"
                        >
                            <svg class="w-4 h-4 text-accent-sage" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="23 4 23 10 17 10"/>
                                <polyline points="1 20 1 14 7 14"/>
                                <path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/>
                            </svg>
                            <span>Acak lagi</span>
                        </a>
                    </div>
                @endif
            </div>
        @endif

        <!-- Conflict Modal (Reusing existing cart replacement modal) -->
        <div
            x-show="conflictModalOpen"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
        >
            <div
                @click.away="conflictModalOpen = false"
                class="bg-surface border border-line rounded-3xl max-w-sm w-full p-6 shadow-2xl text-center space-y-4"
            >
                <div class="w-14 h-14 mx-auto rounded-full bg-butter/20 text-butter flex items-center justify-center">
                    <svg class="w-7 h-7 text-text-main" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                        <line x1="12" y1="9" x2="12" y2="13"/>
                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-[17px] font-bold font-heading text-text-main">Ganti Kantin?</h3>
                    <p class="text-[13px] text-muted mt-1 leading-relaxed">
                        Keranjangmu saat ini berisi menu dari <span class="font-bold text-text-main" x-text="conflictCurrentCanteen"></span>. Satu keranjang hanya boleh berisi menu dari satu kantin.
                    </p>
                    <p class="text-[13px] text-brand font-semibold mt-2">
                        Kosongkan keranjang dan mulai memesan dari <span x-text="conflictNewCanteen"></span>?
                    </p>
                </div>
                <div class="flex gap-2.5 pt-2">
                    <button
                        type="button"
                        @click="conflictModalOpen = false"
                        class="flex-1 py-3 px-3 border border-line bg-surface rounded-xl text-xs font-bold text-text-main hover:bg-page transition"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="confirmReplaceCart()"
                        class="flex-1 py-3 px-3 bg-brand text-on-brand rounded-xl text-xs font-bold hover:opacity-90 transition shadow-sm"
                    >
                        Ya, Ganti Kantin
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-layouts.buyer>

