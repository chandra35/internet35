# UI Reference — Admin Customer List (`/admin/customers`)

> Last updated: 2026-05-20 (commit `cd7247c`)

---

## 1. Stat Cards (atas halaman)

**Template:** `resources/views/admin/customers/index.blade.php`

Portal-style gradient tiles — bukan AdminLTE `small-box`. Setiap card adalah `<a>` yang link ke filtered list.

### HTML pattern
```html
<div class="col-6 col-md-3 mb-2 mb-md-0">
    <a href="{{ route('admin.customers.index', [...filter...]) }}" class="stat-card stat-blue">
        <i class="fas fa-users sc-icon"></i>
        <div class="sc-value">{{ number_format($stats['total']) }}</div>
        <div class="sc-label">Total Pelanggan</div>
        {{-- opsional: --}}
        <span class="sc-link">Filter aktif →</span>
    </a>
</div>
```

### Cards yang ada
| Card         | Class         | Icon                   | Filter URL            |
|--------------|---------------|------------------------|-----------------------|
| Total        | `stat-blue`   | `fa-users`             | `?pop_id=X`           |
| Aktif        | `stat-green`  | `fa-check-circle`      | `?status=active`      |
| Pending      | `stat-yellow` | `fa-clock`             | `?status=pending`     |
| Suspended    | `stat-red`    | `fa-ban`               | `?status=suspended`   |

### CSS classes
```css
/* Defined di @section('css') dalam index.blade.php */

.stat-card {
    border-radius: 10px; padding: 14px 16px 12px; color: #fff;
    position: relative; overflow: hidden; cursor: pointer;
    transition: transform 0.18s, box-shadow 0.18s;
    text-decoration: none; display: block;
}
.stat-card:hover { transform: translateY(-3px); box-shadow: 0 7px 22px rgba(0,0,0,0.22); }

.stat-card .sc-icon  { position: absolute; right: 12px; top: 10px; font-size: 32px; opacity: 0.14; pointer-events: none; }
.stat-card .sc-value { font-size: 1.6rem; font-weight: 700; line-height: 1.2; }
.stat-card .sc-label { font-size: 0.7rem; opacity: 0.88; margin-top: 2px; }
.stat-card .sc-link  { display: block; color: rgba(255,255,255,0.72); font-size: 0.65rem;
                        margin-top: 7px; border-top: 1px solid rgba(255,255,255,0.18); padding-top: 5px; }

/* Gradient backgrounds */
.stat-blue  { background: linear-gradient(135deg, #1565c0, #1976d2); }
.stat-green { background: linear-gradient(135deg, #1aaa55, #17c671); }
.stat-yellow{ background: linear-gradient(135deg, #e0871a, #f4a721); }
.stat-red   { background: linear-gradient(135deg, #dc3545, #c82333); }
.stat-teal  { background: linear-gradient(135deg, #00838f, #0097a7); }  /* tersedia, belum dipakai */
```

> **Referensi asal:** CSS ini exact copy dari `resources/views/layouts/pelanggan.blade.php` (portal customer),
> halaman `/pelanggan/invoices`. Kedua halaman kini konsisten secara visual.

---

## 2. Main Card (tabel daftar pelanggan)

**Template:** `resources/views/admin/customers/index.blade.php`

```html
<div class="card card-customers shadow-sm" style="border-radius:10px;">
    <div class="card-header" style="border-bottom:1px solid rgba(255,255,255,0.12);">
        <h3 class="card-title"><i class="fas fa-users mr-2"></i>Daftar Pelanggan</h3>
        <div class="card-tools">
            <!-- tombol Import (btn-light btn-sm) dan Tambah (btn-warning btn-sm) -->
        </div>
    </div>
    <div class="card-body p-3">
        @include('admin.customers._table', ...)
    </div>
</div>
```

### CSS `.card-customers`
```css
/* Defined di @section('css') dalam index.blade.php */
.card-customers { border: none !important; border-radius: 10px !important; overflow: hidden; }
.card-header-customers, .card-customers > .card-header {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    border-bottom: none; padding: 10px 16px;
}
.card-customers > .card-header .card-title { color: white; font-size: 0.9rem; font-weight: 600; }
```

---

## 3. Tabel Pelanggan

**Template:** `resources/views/admin/customers/_table.blade.php`

### Row status border
Setiap `<tr>` mendapat class `row-{status}` yang menambah accent border kiri berwarna:

```blade
<tr class="row-{{ $customer->status }}">
```

```css
/* di index.blade.php @section('css') */
.row-active    { box-shadow: inset 3px 0 0 #1aaa55; }   /* hijau */
.row-pending   { box-shadow: inset 3px 0 0 #f4a721; }   /* oranye */
.row-suspended { box-shadow: inset 3px 0 0 #dc3545; }   /* merah */
.row-terminated{ box-shadow: inset 3px 0 0 #868e96; }   /* abu */
```

> Teknik `box-shadow: inset` digunakan karena `border-left` pada `<tr>` tidak konsisten di semua browser.

### Avatar initials
```php
$avatarColors = ['#4e73df','#1cc88a','#36b9cc','#e74a3b','#f6c23e','#6f42c1','#fd7e14','#20c9a6','#858796'];
$avatarBg = $avatarColors[abs(crc32($customer->name)) % count($avatarColors)];
$initial   = strtoupper(mb_substr($customer->name, 0, 1));
```
> `abs(crc32(...))` wajib pakai `abs()` karena `crc32()` bisa return negatif di PHP.

### Kolom tabel (8 kolom)
| # | Kolom         | Keterangan                          |
|---|---------------|-------------------------------------|
| 1 | Checkbox      | Bulk action select                  |
| 2 | Pelanggan     | Avatar + Nama + username PPPoE      |
| 3 | Paket         | Nama paket + badge router           |
| 4 | Status        | Badge warna: success/warning/danger |
| 5 | IP Address    | `monospace` kecil                   |
| 6 | POP           | Nama POP                            |
| 7 | Bergabung     | Format tanggal                      |
| 8 | Aksi          | Tombol bulat: lihat/edit/hapus      |

### Filter tags
Di atas tabel muncul badge filter aktif (hasil dari `$request->only([...])`):
```blade
@foreach(['status','package_id','router_id','city'] as $key)
    @if(request($key))
    <span class="filter-tag badge badge-secondary">{{ ucfirst($key) }}: {{ ... }} <a href="...">×</a></span>
    @endif
@endforeach
```

---

## 4. Filter Bar

```html
<div class="filter-bar mb-3">
    <form id="filterForm" method="GET" ...>
        <!-- input search, select status, select paket, select router -->
        <button class="btn btn-primary btn-sm">Filter</button>
        <a href="..." class="btn btn-secondary btn-sm">Reset</a>
    </form>
</div>
```

Filter bar juga dikendalikan JS AJAX: form submit → `loadTable()` → replace `#tableWrapper` tanpa full reload.

---

## 5. AJAX Table Reload

**File JS inline di `index.blade.php` `@section('js')`**

- `loadTable(url)` — kirim AJAX GET dengan header `X-Requested-With: XMLHttpRequest`
- Response dari controller: partial `admin.customers._table` (ketika `$request->ajax()`)
- URL di-push ke browser history via `history.pushState()`
- Event delegation pada `#tableWrapper` untuk pagination links

```js
function loadTable(url) {
    $('#tableWrapper').css('opacity', 0.5);
    $.ajax({ url: url.toString(), headers: {'X-Requested-With': 'XMLHttpRequest'},
        success: function(html) {
            $('#tableWrapper').html(html).css('opacity', 1);
            history.pushState(null, '', url.toString());
        }
    });
}
```

---

## 6. Warna Referensi (Bootstrap 4 + theme)

| Token        | Hex       | Dipakai di                    |
|--------------|-----------|-------------------------------|
| primary      | `#007bff` | link aktif, focus              |
| success      | `#28a745` | badge aktif                   |
| warning      | `#ffc107` | badge pending, btn Tambah     |
| danger       | `#dc3545` | badge suspended, stat-red     |
| secondary    | `#6c757d` | tombol Kembali/Reset          |
| stat-blue    | `#1565c0→#1976d2` | stat card total      |
| stat-green   | `#1aaa55→#17c671` | stat card aktif      |
| stat-yellow  | `#e0871a→#f4a721` | stat card pending    |
| stat-red     | `#dc3545→#c82333` | stat card suspended  |
| header grad  | `#1e3c72→#2a5298` | card-header utama    |
