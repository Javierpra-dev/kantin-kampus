# Dokumentasi REST API Kantin Kampus (Phase 12a)

API REST untuk aplikasi mobile pembeli (Flutter Buyer App). Pembeli tidak memerlukan login akun. Keamanan akses pesanan menggunakan token rahasia (`X-Order-Token`) yang dihasilkan server saat pesanan dibuat.

**Base URL**: `http://localhost:8000/api/v1` (atau host server Anda)  
**Header Umum**:
```http
Accept: application/json
Content-Type: application/json
```

---

## 1. Kantin & Menu

### `GET /canteens`
Mengambil daftar seluruh kantin kampus.

**Request:**
```http
GET /api/v1/canteens
```

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Kantin Bu Siti",
      "description": "Menyediakan aneka masakan rumahan dan ayam geprek.",
      "photo_url": "http://localhost:8000/storage/canteens/photo1.jpg",
      "is_open": true,
      "open_time": "08:00:00",
      "close_time": "17:00:00",
      "popular_menus": ["Ayam Geprek", "Es Teh Manis"],
      "crowd_level": null,
      "active_orders_count": null,
      "estimate_minutes": null
    }
  ]
}
```

---

### `GET /canteens/{id}/menus`
Mengambil daftar menu dari satu kantin tertentu.

**Request:**
```http
GET /api/v1/canteens/1/menus
```

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 10,
      "canteen_id": 1,
      "name": "Ayam Geprek Sambal Korek",
      "description": "Ayam krispi digeprek pedas gurih.",
      "category": "Makanan berat",
      "price": 15000,
      "promo_price": 12000,
      "effective_price": 12000,
      "is_promo_active": true,
      "daily_stock": 4,
      "is_available": true,
      "is_out_of_stock": false,
      "flavor_tags": ["pedas", "hangat"],
      "photo_url": "http://localhost:8000/storage/menus/geprek.jpg"
    }
  ]
}
```

---

### `GET /menus/popular`
Daftar hingga 8 menu populer dari kantin yang buka.

**Request:**
```http
GET /api/v1/menus/popular
```

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 10,
      "name": "Ayam Geprek Sambal Korek",
      "effective_price": 12000,
      "category": "Makanan berat",
      "photo_url": "http://localhost:8000/storage/menus/geprek.jpg"
    }
  ]
}
```

---

### `GET /menus/mood?tag=segar|hangat|pedas`
Daftar hingga 8 menu rekomendasi mood.

**Request:**
```http
GET /api/v1/menus/mood?tag=segar
```

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 12,
      "name": "Es Jeruk Peras",
      "effective_price": 5000,
      "category": "Minuman",
      "flavor_tags": ["segar"],
      "photo_url": "http://localhost:8000/storage/menus/esjeruk.jpg"
    }
  ]
}
```

---

## 2. Pencarian & Zona

### `GET /search?q=...&category=...&max_price=...`
Pencarian cerdas menu & kantin dengan pencocokan kata (AND) dan sinonim bahasa Indonesia.

**Request:**
```http
GET /api/v1/search?q=es%20teh&max_price=10000
```

**Response (200 OK):**
```json
{
  "data": {
    "query": "es teh",
    "menus": [
      {
        "id": 15,
        "name": "Es Teh Manis Jumbo",
        "effective_price": 4000,
        "price": 4000,
        "is_available": true,
        "canteen": {
          "id": 1,
          "name": "Kantin Bu Siti",
          "is_open": true
        }
      }
    ],
    "canteens": [],
    "total": 1
  }
}
```

---

### `GET /zones`
Daftar zona / titik antar kampus.

**Request:**
```http
GET /api/v1/zones
```

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Perpustakaan Pusat Lantai 1",
      "code": "LIB1",
      "delivery_fee": 2000,
      "description": "Di dekat pintu masuk utama.",
      "photo_url": null
    }
  ]
}
```

---

## 3. Rekomendasi "Pilihin Aku"

### `POST /suggestions`
Menghasilkan hingga 3 kombinasi menu (makanan + minuman dari kantin yang sama) sesuai budget dan mood pembeli.

**Request:**
```http
POST /api/v1/suggestions
Content-Type: application/json

{
  "budget": 20000,
  "mood": "segar"
}
```

**Response (200 OK):**
```json
{
  "data": [
    {
      "canteen": {
        "id": 1,
        "name": "Kantin Bu Siti",
        "is_open": true
      },
      "is_combo": true,
      "total": 17000,
      "main": {
        "id": 10,
        "name": "Ayam Geprek",
        "effective_price": 12000
      },
      "drink": {
        "id": 12,
        "name": "Es Jeruk",
        "effective_price": 5000
      }
    }
  ]
}
```

---

## 4. Pemesanan (Orders)

### `POST /orders`
Membuat pesanan baru. Harga dihitung sepenuhnya di server (server-side pricing dengan row lock stok).

**Request:**
```http
POST /api/v1/orders
Content-Type: application/json

{
  "canteen_id": 1,
  "customer_name": "Dimas",
  "customer_whatsapp": "081234567890",
  "zone_id": 1,
  "location_note": "Meja dekat jendela",
  "items": [
    {
      "menu_id": 10,
      "quantity": 2,
      "note": "Pedas sedang"
    }
  ]
}
```
*(Catatan: Bisa juga dikirim sebagai `multipart/form-data` bila melampirkan file `location_photo`)*

**Response (201 Created):**
```json
{
  "data": {
    "order_code": "ORD-20261103-ABCD",
    "order_token": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0",
    "status": "menunggu_bayar",
    "payment_status": "belum_bayar",
    "payment_method": "qris",
    "customer_name": "Dimas",
    "customer_whatsapp": "081234567890",
    "subtotal": 24000,
    "delivery_fee": 2000,
    "total": 26000,
    "items": [
      {
        "id": 1,
        "menu_id": 10,
        "name": "Ayam Geprek",
        "unit_price": 12000,
        "quantity": 2,
        "subtotal": 24000,
        "note": "Pedas sedang"
      }
    ]
  }
}
```

**Response Error (409 Conflict):**
```json
{
  "message": "Sisa porsi Ayam Geprek tinggal 1."
}
```

---

### `GET /orders/{order_code}`
Mengambil detail dan status pesanan terkini. Wajib menyertakan header `X-Order-Token`.

**Request:**
```http
GET /api/v1/orders/ORD-20261103-ABCD
X-Order-Token: a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0
```

**Response (200 OK):**
```json
{
  "data": {
    "order_code": "ORD-20261103-ABCD",
    "status": "diproses",
    "payment_status": "dibayar",
    "total": 26000,
    "canteen": { "id": 1, "name": "Kantin Bu Siti" },
    "zone": { "id": 1, "name": "Perpustakaan Pusat", "delivery_fee": 2000 },
    "timestamps": {
      "created_at": "2026-11-03T10:00:00+07:00",
      "processed_at": "2026-11-03T10:02:00+07:00",
      "delivered_at": null,
      "completed_at": null
    },
    "items": [...]
  }
}
```

---

### `POST /orders/{order_code}/payment`
Menghasilkan atau mengambil string QRIS pembayaran Midtrans. Wajib menyertakan header `X-Order-Token`.

**Request:**
```http
POST /api/v1/orders/ORD-20261103-ABCD/payment
X-Order-Token: a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0
```

**Response (200 OK):**
```json
{
  "data": {
    "order_code": "ORD-20261103-ABCD",
    "amount": 26000,
    "qr_string": "00020101021226500016ID.CO.KANTIN.WWW...",
    "qr_image_url": "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=...",
    "expires_at": "2026-11-03T10:15:00+07:00",
    "simulation": true
  }
}
```

---

### `POST /orders/{order_code}/simulate-pay`
Mensimulasikan pembayaran berhasil saat testing/development (`PAYMENT_SIMULATION=true`). Menghasilkan status 404 jika simulasi dinonaktifkan.

**Request:**
```http
POST /api/v1/orders/ORD-20261103-ABCD/simulate-pay
X-Order-Token: a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0
```

**Response (200 OK):**
```json
{
  "data": {
    "order_code": "ORD-20261103-ABCD",
    "status": "diproses",
    "payment_status": "dibayar"
  }
}
```

---

### `POST /orders/{order_code}/reorder-check`
Mengecek apakah menu pada pesanan sebelumnya masih tersedia dan buka untuk dipesan ulang.

**Request:**
```http
POST /api/v1/orders/ORD-20261103-ABCD/reorder-check
X-Order-Token: a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0
```

**Response (200 OK):**
```json
{
  "data": {
    "canteen_id": 1,
    "canteen_name": "Kantin Bu Siti",
    "canteen_is_open": true,
    "items": [
      {
        "menu_id": 10,
        "name": "Ayam Geprek",
        "requested_quantity": 2,
        "is_available": true,
        "current_stock": 5,
        "current_effective_price": 12000
      }
    ]
  }
}
```

