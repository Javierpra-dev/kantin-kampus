# PROJECT BRIEF: Kantin Kampus

Online Campus Food Delivery platform for university students and canteen kitchens.

## Design Tokens & System
- Page background: `#F7F8F4`
- Surface: `#FFFFFF`
- Soft sage tint: `#E6EEE6`
- Sage (accents, active states): `#5B8C6B`
- Deep green (primary buttons, cart bar, totals, key headings): `#2F4A3A`
- Text: `#1E2A24`
- Muted text: `#78857D`
- Border: `#E3E8E1`
- Butter (tiny badges): `#F2C94C`
- Danger (errors): `#D64545`
- Typography:
  - Headings: `Bricolage Grotesque` (bold/extrabold, tight letter-spacing)
  - Body: `Plus Jakarta Sans`
  - Scale: 28 / 22 / 17 / 15 / 13 px
- Shapes:
  - Images: 22px border radius
  - Cards: 16px border radius
  - Inputs: 14px border radius
  - Primary buttons: fully rounded pill (52px tall)
  - Chips: pill
- Rules: NO gradients, NO emoji in UI, NO blue/purple. All UI text in Indonesian. Mobile-first design (max-width 480px centered for buyer views, up to 768px for kitchen admin).

---

## Database Schema

### 1. `canteens`
- `id` (bigint, PK)
- `name` (string)
- `slug` (string, unique)
- `description` (text, nullable)
- `photo` (string, nullable)
- `is_open` (boolean, default true)
- `opening_hours` (string, nullable)
- `open_time` (string, nullable)
- `close_time` (string, nullable)
- `phone` (string, nullable)
- `whatsapp` (string, nullable) - optional WhatsApp contact number for buyers
- `created_at`, `updated_at`

### 2. `menus`
- `id` (bigint, PK)
- `canteen_id` (foreignId -> `canteens`, cascade)
- `name` (string)
- `description` (text, nullable)
- `price` (integer)
- `category` (string, default "Makanan berat") - allowed: "Makanan berat", "Minuman", "Camilan"
- `photo` (string, nullable)
- `is_available` (boolean, default true)
- `is_popular` (boolean, default false)
- `flavor_tags` (nullable JSON array) - allowed values inside array: `"segar"`, `"hangat"`, `"pedas"`
- `daily_stock` (unsigned int, nullable) - remaining portions today, null = no limit
- `promo_price` (unsigned int, nullable) - promotional price, null = no promo
- `promo_start` (time, nullable) - promo start time (HH:MM:SS)
- `promo_end` (time, nullable) - promo end time (HH:MM:SS)
- `created_at`, `updated_at`

### 3. `admins` / `users`
- `id` (bigint, PK)
- `canteen_id` (foreignId -> `canteens`, nullable)
- `username` (string, unique)
- `password` (string, hashed)
- `name` (string)
- `role` (string)
- `created_at`, `updated_at`

### 4. `zones` (8 campus delivery spots)
- `id` (bigint, PK)
- `name` (string)
- `description` (string, nullable)
- `photo` (string, nullable)
- `created_at`, `updated_at`

### 5. `orders`
- `id` (bigint, PK)
- `order_code` (string, unique)
- `access_token` (string 40, unique, nullable) - secret token for buyer API access without account
- `canteen_id` (foreignId -> `canteens`)
- `zone_id` (foreignId -> `zones`)
- `customer_name` (string)
- `customer_phone` (string, nullable)
- `location_notes` (text, nullable)
- `location_photo` (string, nullable) - stored on private disk, stripped of EXIF/GPS, deleted on order completion or >24h
- `had_location_photo` (boolean, default false) - stays true for research/mis-delivery tracking
- `total_price` (integer)
- `payment_status` (string: "menunggu_pembayaran", "dibayar", "kedaluwarsa")
- `order_status` (string: "menunggu_pembayaran", "baru", "diproses", "diantar", "selesai", "dibatalkan")
- `payment_method` (string: "qris")
- `payment_reference` (string, nullable)
- `payment_expired_at` (timestamp, nullable)
- `paid_at` (timestamp, nullable)
- `processed_at` (timestamp, nullable)
- `delivered_at` (timestamp, nullable)
- `completed_at` (timestamp, nullable)
- `created_at`, `updated_at`

### 6. `order_items`
- `id` (bigint, PK)
- `order_id` (foreignId -> `orders`, cascade)
- `menu_id` (foreignId -> `menus`)
- `name` (string)
- `price` (integer)
- `quantity` (integer)
- `notes` (string, nullable)
- `created_at`, `updated_at`

### 7. `suggestion_logs`
- `id` (bigint, PK)
- `budget` (integer)
- `mood` (string) - "segar", "hangat", "pedas", "bebas"
- `results_count` (integer)
- `chosen` (boolean, default false) - marked true when "Pesan ini" is clicked
- `created_at`, `updated_at`

---

## REST API (Phase 12a - Flutter Buyer App)
Base URL: `/api/v1`
Format: JSON (`{"data": ...}` on success, `{"message": "...", "errors": {...}}` on error)
Rate limit: 60 request/menit (global), 10 request/menit (`POST /orders`).
Buyer Auth: No account needed; order tracking protected by `X-Order-Token` (40-char secret access token).

### Endpoints
- `GET /api/v1/canteens` : Daftar semua kantin beserta menu populer dan status buka/tutup.
- `GET /api/v1/canteens/{id}/menus` : Daftar menu kantin beserta harga efektif promo & sisa stok.
- `GET /api/v1/menus/popular` : Menu populer (maks 8, tersedia).
- `GET /api/v1/menus/mood?tag=segar|hangat|pedas` : Menu rekomendasi mood (maks 8, tersedia).
- `GET /api/v1/search?q=...&category=...&max_price=...` : Pencarian live menu & kantin.
- `GET /api/v1/zones` : Daftar zona pengantaran kampus aktif dan ongkir.
- `POST /api/v1/suggestions` : Fitur "Pilihin Aku" combo hemat (budget & mood).
- `POST /api/v1/orders` : Pembuatan pesanan pembeli (server-side pricing, row-locking stok). Mengembalikan `order_token`.
- `GET /api/v1/orders/{order_code}` : Pelacakan status pesanan (header `X-Order-Token`).
- `POST /api/v1/orders/{order_code}/payment` : Permintaan QRIS Midtrans (header `X-Order-Token`).
- `POST /api/v1/orders/{order_code}/simulate-pay` : Simulasi pembayaran lunas (hanya jika `PAYMENT_SIMULATION=true`).
- `POST /api/v1/orders/{order_code}/reorder-check` : Pengecekan ketersediaan menu untuk pesan ulang.


