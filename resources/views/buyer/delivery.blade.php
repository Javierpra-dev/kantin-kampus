<x-layouts.buyer title="Kamu duduk di mana?" :hideNav="true">
    <x-page-header title="Kamu duduk di mana?" :backUrl="route('cart.index')" />

    <form
        action="{{ route('order.store') }}"
        method="POST"
        enctype="multipart/form-data"
        x-data="{
            selectedZone: {{ old('zone_id', 'null') }},
            selectedZoneName: '{{ old('zone_id') ? ($zones->firstWhere('id', old('zone_id'))?->name ?? '') : '' }}',
            photoPreview: null,
            hasPhoto: false,
            isCompressing: false,
            isOtherZone() {
                const name = this.selectedZoneName.toLowerCase();
                return name.includes('tidak yakin') || name.includes('lainnya');
            },
            handlePhoto(e) {
                const file = e.target.files[0];
                if (!file) return;

                this.isCompressing = true;
                const reader = new FileReader();
                reader.onload = (event) => {
                    const img = new Image();
                    img.onload = () => {
                        const maxDim = 1200;
                        let width = img.width;
                        let height = img.height;

                        if (width > maxDim || height > maxDim) {
                            if (width > height) {
                                height = Math.round((height * maxDim) / width);
                                width = maxDim;
                            } else {
                                width = Math.round((width * maxDim) / height);
                                height = maxDim;
                            }
                        }

                        const canvas = document.createElement('canvas');
                        canvas.width = width;
                        canvas.height = height;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, width, height);

                        canvas.toBlob((blob) => {
                            this.isCompressing = false;
                            if (!blob) return;

                            try {
                                const compressedFile = new File([blob], 'location_photo.jpg', {
                                    type: 'image/jpeg',
                                    lastModified: Date.now()
                                });
                                const dataTransfer = new DataTransfer();
                                dataTransfer.items.add(compressedFile);
                                this.$refs.photoInput.files = dataTransfer.files;
                            } catch (err) {
                                console.warn('DataTransfer not supported, falling back to original file', err);
                            }

                            this.photoPreview = canvas.toDataURL('image/jpeg', 0.7);
                            this.hasPhoto = true;
                        }, 'image/jpeg', 0.7);
                    };
                    img.src = event.target.result;
                };
                reader.readAsDataURL(file);
            },
            removePhoto() {
                this.photoPreview = null;
                this.hasPhoto = false;
                this.isCompressing = false;
                if (this.$refs.photoInput) {
                    this.$refs.photoInput.value = '';
                }
            }
        }"
        class="p-4 space-y-5"
    >
        @csrf

        <!-- Order Summary Pill -->
        <div class="flex items-center justify-between p-3 bg-surface rounded-2xl border border-line shadow-sm text-xs">
            <div class="flex items-center gap-2">
                <span class="text-brand font-bold">🏪 {{ $cart['canteen_name'] }}</span>
                <span class="text-line">•</span>
                <span class="text-muted">{{ $cart['count'] }} item</span>
            </div>
            <span class="font-extrabold text-brand">@rupiah($cart['total'])</span>
        </div>

        <!-- Section 1: Data Pemesan -->
        <div class="bg-surface rounded-3xl border border-line shadow-sm p-4 space-y-4">
            <h2 class="text-xs font-bold text-text-main uppercase tracking-wider flex items-center gap-1.5">
                <span class="w-1.5 h-3.5 bg-brand rounded-full"></span>
                Data Pemesan
            </h2>

            <div>
                <label for="customer_name" class="block text-xs font-semibold text-text-main mb-1.5">
                    Nama Pemesan <span class="text-danger">*</span>
                </label>
                <input
                    type="text"
                    name="customer_name"
                    id="customer_name"
                    required
                    value="{{ old('customer_name') }}"
                    placeholder="Masukkan nama lengkap / panggilan"
                    class="block w-full rounded-xl border border-line bg-surface px-3.5 py-2.5 text-sm text-text-main placeholder:text-muted focus:border-accent-sage focus:ring-1 focus:ring-accent-sage transition @error('customer_name') border-danger @enderror"
                />
                @error('customer_name')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="customer_phone" class="block text-xs font-semibold text-text-main mb-1.5">
                    Nomor WhatsApp <span class="text-xs font-normal text-muted">(Opsional)</span>
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-xs text-muted pointer-events-none">
                        🇮🇩 +62 / 08
                    </span>
                    <input
                        type="tel"
                        name="customer_phone"
                        id="customer_phone"
                        value="{{ old('customer_phone') }}"
                        placeholder="812-3456-7890"
                        class="block w-full rounded-xl border border-line bg-surface pl-20 pr-3.5 py-2.5 text-sm text-text-main placeholder:text-muted focus:border-accent-sage focus:ring-1 focus:ring-accent-sage transition @error('customer_phone') border-danger @enderror"
                    />
                </div>
                <p class="mt-1 text-[11px] text-muted">Untuk konfirmasi pesanan dan update pengantaran.</p>
                @error('customer_phone')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Section 2: Pilih Lokasi Duduk -->
        <div class="bg-surface rounded-3xl border border-line shadow-sm p-4 space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="text-xs font-bold text-text-main uppercase tracking-wider flex items-center gap-1.5">
                    <span class="w-1.5 h-3.5 bg-brand rounded-full"></span>
                    Pilih Lokasi Duduk <span class="text-danger">*</span>
                </h2>
                <span class="text-[11px] text-muted">Pilih salah satu</span>
            </div>

            <input type="hidden" name="zone_id" :value="selectedZone" />

            @error('zone_id')
                <p class="text-xs text-danger font-medium">{{ $message }}</p>
            @enderror

            <div class="grid grid-cols-2 gap-3 pt-1">
                @foreach ($zones as $zone)
                    @php
                        $isOther = str_contains(strtolower($zone->name), 'tidak yakin') || str_contains(strtolower($zone->name), 'lainnya');
                    @endphp
                    <div
                        @click="selectedZone = {{ $zone->id }}; selectedZoneName = '{{ addslashes($zone->name) }}'"
                        class="relative rounded-2xl cursor-pointer overflow-hidden transition-all duration-200 text-left flex flex-col justify-between"
                        :class="selectedZone === {{ $zone->id }}
                            ? 'border-2 border-accent-sage bg-sage-tint/50 shadow-sm ring-2 ring-accent-sage/25'
                            : 'border border-line bg-surface hover:border-muted hover:shadow-xs'"
                    >
                        <!-- Thumbnail Photo -->
                        <div class="relative h-24 w-full bg-sage-tint overflow-hidden">
                            <img
                                src="{{ $zone->photo ?? 'https://images.unsplash.com/photo-1541829070764-84a7d30dd3f3?w=300' }}"
                                alt="{{ $zone->name }}"
                                class="w-full h-full object-cover transition duration-300"
                                loading="lazy"
                            />
                            <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-black/10"></div>

                            <div
                                x-show="selectedZone === {{ $zone->id }}"
                                x-cloak
                                class="absolute top-2 right-2 w-6 h-6 rounded-full bg-accent-sage text-white flex items-center justify-center shadow-md"
                            >
                                <svg class="w-4 h-4 stroke-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                        </div>

                        <!-- Zone Name -->
                        <div class="p-2.5">
                            <h3 class="text-xs font-bold text-text-main leading-snug line-clamp-2">
                                {{ $zone->name }}
                            </h3>
                            @if ($zone->description)
                                <p class="text-[10px] text-muted mt-1 line-clamp-1">
                                    {{ $zone->description }}
                                </p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Section 3: Keterangan lokasi & Foto tempat duduk -->
        <div class="bg-surface rounded-3xl border border-line shadow-sm p-4 space-y-4">
            <div>
                <label for="location_notes" class="block text-xs font-semibold text-text-main mb-1">
                    Keterangan Lokasi
                    <span x-show="isOtherZone() && !hasPhoto" x-cloak class="text-danger font-bold">* (Wajib diisi jika tanpa foto)</span>
                    <span x-show="!isOtherZone() || hasPhoto" class="text-xs font-normal text-muted">(Opsional)</span>
                </label>
                <textarea
                    name="location_notes"
                    id="location_notes"
                    rows="2"
                    :required="isOtherZone() && !hasPhoto"
                    placeholder="mis. meja dekat tiang, jaket hitam"
                    class="block w-full rounded-xl border border-line bg-surface px-3.5 py-2.5 text-sm text-text-main placeholder:text-muted focus:border-accent-sage focus:ring-1 focus:ring-accent-sage transition @error('location_notes') border-danger @enderror"
                >{{ old('location_notes') }}</textarea>
                <p class="mt-1 text-[11px] text-muted">
                    Bantu kurir kantin menemukan posisi duduk Anda dengan ciri-ciri meja atau pakaian.
                </p>
                @error('location_notes')
                    <p class="mt-1 text-xs text-danger font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Optional Seating Photo -->
            <div class="pt-2 border-t border-line">
                <span class="block text-xs font-semibold text-text-main mb-1.5">
                    Foto Tempat Duduk
                    <span class="text-xs font-normal text-muted">(Opsional)</span>
                </span>

                <input
                    type="file"
                    x-ref="photoInput"
                    name="location_photo"
                    id="location_photo"
                    accept="image/*"
                    capture="environment"
                    class="hidden"
                    @change="handlePhoto($event)"
                />

                <div x-show="!hasPhoto">
                    <button
                        type="button"
                        @click="$refs.photoInput.click()"
                        :disabled="isCompressing"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-accent-sage bg-sage-tint/50 hover:bg-sage-tint text-brand text-xs font-bold transition focus:outline-none focus:ring-2 focus:ring-accent-sage/20 active:scale-98 cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span x-text="isCompressing ? 'Memproses foto...' : 'Tambah foto tempat dudukmu'">Tambah foto tempat dudukmu</span>
                    </button>
                    <p class="mt-1.5 text-[11px] text-muted">
                        Bantu penjual menemukanmu. Hindari memotret wajah orang lain.
                    </p>
                </div>

                <div x-show="hasPhoto" x-cloak class="flex items-center gap-3 pt-1">
                    <div class="relative w-20 h-20 rounded-xl overflow-hidden border border-line bg-page flex-shrink-0">
                        <img :src="photoPreview" alt="Preview foto tempat duduk" class="w-full h-full object-cover" />
                    </div>
                    <div class="space-y-1">
                        <span class="block text-xs font-semibold text-brand">Foto siap dilampirkan</span>
                        <button
                            type="button"
                            @click="removePhoto()"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-danger hover:opacity-80 hover:underline cursor-pointer"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            <span>Hapus foto</span>
                        </button>
                    </div>
                </div>

                @error('location_photo')
                    <p class="mt-1 text-xs text-danger font-medium">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Primary Button -->
        <div class="pt-2 pb-6">
            <x-button type="submit" variant="primary" size="lg" class="w-full">
                <span>Lanjut ke pembayaran</span>
                <svg class="w-5 h-5 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </x-button>
        </div>
    </form>
</x-layouts.buyer>
