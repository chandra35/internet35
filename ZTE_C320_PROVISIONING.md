# ZTE C320 OLT — Panduan Provisioning ONU dari Nol

> Dokumen ini dihasilkan dari analisis `ZteC320Helper.php` dan hasil pengujian langsung pada perangkat ZTE C320 (firmware V2.1.0).  
> Tujuan: Acuan teknis membangun aplikasi provisioning C320 dari nol.

---

## Daftar Isi

1. [Arsitektur & Koneksi OLT](#1-arsitektur--koneksi-olt)
2. [Konsep GPON Dasar](#2-konsep-gpon-dasar)
3. [Pra-Syarat: Profile yang Harus Ada](#3-pra-syarat-profile-yang-harus-ada)
4. [Alur Register ONU — Lengkap](#4-alur-register-onu--lengkap)
5. [Detail Perintah Per Tahap](#5-detail-perintah-per-tahap)
6. [Konfigurasi pon-onu-mng (OMCI)](#6-konfigurasi-pon-onu-mng-omci)
7. [Konfigurasi ACS / TR-069](#7-konfigurasi-acs--tr-069)
8. [Unregister ONU](#8-unregister-onu)
9. [Monitoring via SNMP](#9-monitoring-via-snmp)
10. [Monitoring via CLI](#10-monitoring-via-cli)
11. [Troubleshooting & Config State](#11-troubleshooting--config-state)
12. [Referensi OID ZTE](#12-referensi-oid-zte)
13. [Ringkasan Parameter Input Provisioning](#13-ringkasan-parameter-input-provisioning)

---

## 1. Arsitektur & Koneksi OLT

### Interface Naming Convention

```
gpon-olt_1/{slot}/{port}         → PON port (sisi OLT)
gpon-onu_1/{slot}/{port}:{onuId} → ONU interface (sisi ONU, konfigurasi OLT layer)
```

Contoh nyata pada C320 ini:
- `gpon-olt_1/1/1` → Slot 1, Port 1 (kartu GTGH/GTGO)
- `gpon-onu_1/1/1:19` → ONU nomor 19 di port 1/1

### Metode Akses

| Metode | Port | Kegunaan |
|--------|------|----------|
| Telnet | 23   | Konfigurasi CLI (utama) |
| SNMP v2c | 161 | Monitoring read-only |
| SNMP RW | 161 | Konfigurasi via SNMP (terbatas) |

> **Catatan**: Untuk provisionig ONU (register, tcont, gemport, vlan), **CLI/Telnet wajib**. SNMP hanya untuk monitoring.

### Login Sequence Telnet

```
fwrite → "zte\r\n"         # username
fwrite → "zte\r\n"         # password
fwrite → "terminal length 0\r\n"  # nonaktifkan paging (WAJIB sebelum show commands)
```

Setelah login, prompt akan tampil sebagai: `ZXAN#` atau `ZXAN(config)#`

---

## 2. Konsep GPON Dasar

### Komponen yang Saling Berkaitan

```
OLT PON Port (gpon-olt_1/1/1)
    └─ ONU (gpon-onu_1/1/1:19)
           ├─ T-CONT   → "slot" untuk upstream bandwidth (DBA)
           │    └─ Profile TCONT (nama di OLT, berisi parameter DBA)
           ├─ GEM Port  → kanal transport data
           │    ├─ diikat ke T-CONT
           │    └─ diikat ke Traffic Profile (downstream shaping)
           ├─ Service-Port → mapping GEM Port ↔ VLAN ke network
           └─ pon-onu-mng  → konfigurasi OMCI ke dalam ONU
                    ├─ flow    → mapping logical traffic
                    ├─ gemport → arah data ke flow
                    ├─ ip-host → pengaturan IP (DHCP)
                    ├─ veip    → Virtual Ethernet Interface Point (TR-069)
                    ├─ pppoe   → konfigurasi PPPoE
                    └─ tr069-mgmt → URL & credentials ACS
```

### Dua Context CLI Yang Berbeda

| Context | Prefix Perintah | Tujuan |
|---------|----------------|--------|
| `interface gpon-onu_1/1/1:19` | `tcont`, `gemport`, `service-port` | Layer OLT: DBA + VLAN mapping ke jaringan |
| `pon-onu-mng gpon-onu_1/1/1:19` | `flow`, `ip-host`, `pppoe`, `tr069-mgmt` | Layer OMCI: dikonfigurasi ke hardware ONU via GPON |

> **Penting**: `interface gpon-onu_*` = konfigurasi di OLT. `pon-onu-mng gpon-onu_*` = konfigurasi dikirim ke dalam ONU via OMCI. Keduanya **harus** dikonfigurasi.

---

## 3. Pra-Syarat: Profile yang Harus Ada

Sebelum register ONU, dua jenis profile **harus sudah ada** di OLT:

### 3.1 TCONT Profile (Upstream DBA)

Cek dengan: `show gpon profile tcont`

| Nama (contoh) | Type | FBW | ABW | MBW | Fungsi |
|---------------|------|-----|-----|-----|--------|
| `SMARTOLT-1G-UP` | 5 | 64 | 64 | 1048064 | 1G internet upstream |
| `SMARTOLT-VOIPMNG-10M` | 5 | 512 | 1024 | 11264 | Management/TR-069 10M |
| `default` | 1 | 10000 | 0 | 0 | **Hindari! Profile ini sering menyebabkan Config state: fail** |

**Penjelasan DBA Type:**

| Type | Nama | Bandwidth Dijamin | Burst |
|------|------|-------------------|-------|
| 1 | Fixed | ✅ FBW tetap | ❌ |
| 2 | Assured | ✅ ABW minimum | ❌ |
| 3 | Non-Assured | ✅ ABW minimum | ✅ hingga MBW |
| 4 | Best Effort | ❌ tidak dijamin | ✅ hingga MBW |
| **5** | **Hybrid** | **✅ FBW + ABW** | **✅ hingga MBW** |

**Buat TCONT Profile:**
```
configure terminal
gpon profile tcont POP35-1G-UP type 5 fix 64 assure 64 max 1048064
exit
write
```

### 3.2 Traffic Profile (Downstream Shaping)

Cek dengan: `show gpon profile traffic`

| Nama (contoh) | SIR (kbps) | PIR (kbps) | Setara |
|---------------|-----------|-----------|--------|
| `SMARTOLT-1G-DOWN` | 1048064 | 1048064 | ~1 Gbps |
| `SMARTOLT-VOIPMNG-10M` | 10480 | 11264 | ~10 Mbps |
| `default` | 9953280 | 9953280 | uncapped |

- **SIR** = Sustained Information Rate (kecepatan normal)
- **PIR** = Peak Information Rate (burst maksimum, harus ≥ SIR)

**Buat Traffic Profile:**
```
configure terminal
gpon profile traffic POP35-1G-DOWN sir 1048064 pir 1048064
exit
write
```

---

## 4. Alur Register ONU — Lengkap

```
┌─────────────────────────────────────────────────────────────────┐
│  TAHAP 1: Identifikasi ONU (baca unconfigured ONUs)             │
│  show gpon onu uncfg                                            │
│  → Dapatkan: serial number, slot, port                          │
└────────────────────────┬────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────────────┐
│  TAHAP 2: Register ONU di gpon-olt (whitelist serial number)    │
│  interface gpon-olt_1/{slot}/{port}                             │
│    onu {onuId} type {onuType} sn {serialNumber}                 │
│  exit                                                           │
└────────────────────────┬────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────────────┐
│  TAHAP 3: Konfigurasi ONU Interface (OLT layer)                 │
│  interface gpon-onu_1/{slot}/{port}:{onuId}                     │
│    name {nama}                                                  │
│    tcont 1 profile {tcont_profile}   ← UPSTREAM DBA            │
│    gemport 1 tcont 1                 ← Ikat GEM ke T-CONT       │
│    gemport 1 traffic-limit downstream {traffic_profile}         │
│    service-port 1 vport 1 user-vlan {vlan} vlan {vlan}          │
│    [tcont 2 + gemport 2 + service-port 2 jika ada mgmt VLAN]   │
│  exit                                                           │
└────────────────────────┬────────────────────────────────────────┘
                         ↓
                ⏱ Tunggu 8 detik (ONU sync OMCI)
                         ↓
┌─────────────────────────────────────────────────────────────────┐
│  TAHAP 4: Konfigurasi pon-onu-mng (OMCI layer, ke dalam ONU)   │
│  pon-onu-mng gpon-onu_1/{slot}/{port}:{onuId}                   │
│    voip protocol sip                                            │
│    flow 1 pri 0 vlan {vlan}          ← Mapping VLAN internet    │
│    flow 2 pri 2 vlan {mgmtVlan}      ← Mapping VLAN management  │
│    gemport 1 flow 1                  ← Ikat gemport ke flow     │
│    gemport 2 flow 2                                             │
│    flow mode 1 tag-filter vlan-filter untag-filter discard      │
│    switchport-bind switch_0/1 iphost 1                          │
│    switchport-bind switch_0/1 iphost 2                          │
│    switchport-bind switch_0/1 veip 1                            │
│    ip-host 2 dhcp-enable enable ping-response enable            │
│    pppoe 1 nat enable user {user} password {pass}    [opsional] │
│    vlan-filter-mode iphost 1 tag-filter ...                     │
│    vlan-filter iphost 1 pri 0 vlan {vlan}                       │
│    dhcp-ip ethuni eth_0/1 from-onu                              │
│    tr069-mgmt 1 state unlock                                    │
│    tr069-mgmt 1 acs {acsUrl}                                    │
│    tr069-mgmt 1 tag pri 2 vlan {mgmtVlan}                       │
│    security-mgmt 998 state enable ...                           │
│  exit                                                           │
└────────────────────────┬────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────────────┐
│  TAHAP 5: Simpan Konfigurasi                                    │
│  write                                                          │
└────────────────────────┬────────────────────────────────────────┘
                         ↓
┌─────────────────────────────────────────────────────────────────┐
│  TAHAP 6: Verifikasi                                            │
│  show running-config interface gpon-onu_1/{slot}/{port}:{onuId} │
│  show gpon onu detail-info gpon-onu_1/{slot}/{port}:{onuId}     │
│  → Cek: Config state = success (bukan fail)                     │
└─────────────────────────────────────────────────────────────────┘
```

---

## 5. Detail Perintah Per Tahap

### Tahap 1 — Deteksi ONU Baru (Unconfigured)

```bash
show gpon onu uncfg
```

Output:
```
OLT-PORT  ONU-ID  TYPE     SN              State
1/1/1     --      HG8245H  HWTC6ED42F9A   uncfg
```

### Tahap 2 — Register ONU (Whitelist SN)

```bash
configure terminal
interface gpon-olt_1/1/1
  onu 19 type HG8245H sn HWTC6ED42F9A
exit
```

**Catatan ONU Type:**
- Gunakan `HG8245H`, `F670L`, `EG8141H5`, `ALL` (generic)
- `ALL` = tanpa validasi model, berguna untuk ONU brand lain
- Jika type salah → ONU akan masuk `Config state: fail`

### Tahap 3 — Konfigurasi gpon-onu Interface

#### Scenario A: Internet Only (1 VLAN)

```bash
interface gpon-onu_1/1/1:19
  name omah-pelanggan
  tcont 1 profile POP35-1G-UP
  gemport 1 tcont 1
  gemport 1 traffic-limit downstream POP35-1G-DOWN
  service-port 1 vport 1 user-vlan 335 vlan 335
exit
```

#### Scenario B: Internet + Management TR-069 (2 VLAN)

```bash
interface gpon-onu_1/1/1:19
  name omah-pelanggan
  tcont 1 profile POP35-1G-UP
  gemport 1 tcont 1
  gemport 1 traffic-limit downstream POP35-1G-DOWN
  service-port 1 vport 1 user-vlan 335 vlan 335
  tcont 2 profile POP35-MGMT-10M
  gemport 2 tcont 2
  gemport 2 traffic-limit downstream POP35-MGMT-10M
  service-port 2 vport 2 user-vlan 111 vlan 111
exit
```

**Penjelasan:**

| Perintah | Penjelasan |
|---------|-----------|
| `tcont {id} profile {nama}` | Buat T-CONT dan assign profile DBA |
| `gemport {id} tcont {tcontId}` | Buat GEM Port, ikat ke T-CONT |
| `gemport {id} traffic-limit downstream {nama}` | Batasi kecepatan downstream |
| `service-port {id} vport {id} user-vlan {vid} vlan {vid}` | Mapping VLAN: traffic dengan tag `user-vlan` di ONU diteruskan ke VLAN `vlan` di jaringan |

> **service-port**: `user-vlan` = VLAN yang dikasih ke pelanggan di port ONU, `vlan` = VLAN yang beredar di backbone/OLT. Keduanya sama jika transparent tagging (tidak ada QinQ).

---

## 6. Konfigurasi pon-onu-mng (OMCI)

Context ini **dikirim ke hardware ONU** via protokol OMCI over GPON. Harus dilakukan **setelah** ONU selesai sinkronisasi (tunggu ±8 detik setelah konfigurasi interface).

```bash
pon-onu-mng gpon-onu_1/1/1:19
```

### 6.1 Flow (Logical Traffic Channel)

```bash
flow 1 pri 0 vlan 335     # Flow 1 = internet, priority 0, VLAN 335
flow 2 pri 2 vlan 111     # Flow 2 = management TR-069, priority 2, VLAN 111
flow 2 switch switch_0/1  # Bind flow 2 ke switch virtual
```

**Flow Mode** (filter paket masuk):
```bash
flow mode 1 tag-filter vlan-filter untag-filter discard
# tag-filter: terima paket bertag → filter berdasarkan VLAN
# vlan-filter: filter ketat berdasarkan VLAN ID
# untag-filter discard: buang paket untagged
```

### 6.2 GEM Port Binding ke Flow

```bash
gemport 1 flow 1    # GEM Port 1 (internet) → Flow 1
gemport 2 flow 2    # GEM Port 2 (mgmt) → Flow 2
```

### 6.3 Switchport Binding

```bash
switchport-bind switch_0/1 iphost 1   # IP Host 1 (internet DHCP/PPPoE)
switchport-bind switch_0/1 iphost 2   # IP Host 2 (management DHCP)
switchport-bind switch_0/1 veip 1     # VEIP 1 (TR-069 endpoint)
```

> `switch_0/1` = switch virtual internal ONU. `iphost` = interface IP di ONU. `veip` = Virtual Ethernet Interface Point untuk TR-069.

### 6.4 IP Host (DHCP)

```bash
ip-host 2 dhcp-enable enable ping-response enable traceroute-response enable
# ip-host 2 = management interface (terhubung ke VLAN 111)
# Akan mendapat IP dari DHCP server di VLAN 111
```

### 6.5 PPPoE (Internet)

```bash
pppoe 1 nat enable user hisyam password password123
# pppoe 1 = instance PPPoE pertama
# nat enable = aktifkan NAT di ONU
# user/password = credentials PPPoE dial-up
```

> **Catatan penting**: PPPoE di `pon-onu-mng` mengkonfigurasi dial-up **langsung dari ONU** (bukan router). Untuk pelanggan yang punya router di belakang ONU, metode DHCP lebih umum dipakai — PPPoE dilakukan oleh router pelanggan, bukan ONU.

### 6.6 VLAN Filter

```bash
vlan-filter-mode iphost 1 tag-filter vlan-filter untag-filter discard
vlan-filter-mode iphost 2 tag-filter vlan-filter untag-filter discard
vlan-filter iphost 1 pri 0 vlan 335    # iphost 1 hanya terima VLAN 335
vlan-filter iphost 2 pri 2 vlan 111    # iphost 2 hanya terima VLAN 111
```

### 6.7 DHCP di UNI Port Ethernet

```bash
dhcp-ip ethuni eth_0/1 from-onu    # Port LAN 1 ambil IP dari ONU (relay)
dhcp-ip ethuni eth_0/2 from-onu
dhcp-ip ethuni eth_0/3 from-onu
dhcp-ip ethuni eth_0/4 from-onu
```

### 6.8 VoIP (Wajib, meskipun tidak pakai VoIP)

```bash
voip protocol sip
# Harus ada meski tidak ada VoIP, diperlukan agar ONU accept pon-onu-mng context
```

### 6.9 Security Management

```bash
security-mgmt 998 state enable mode forward ingress-type lan protocol web https
security-mgmt 999 state enable ingress-type lan protocol ftp telnet ssh snmp tr069
# Mengizinkan akses manajemen dari sisi LAN ke ONU
```

---

## 7. Konfigurasi ACS / TR-069

TR-069 digunakan untuk manajemen ONU secara remote via GenieACS atau ACS lain. Dikonfigurasi di context `pon-onu-mng`.

### Prasyarat

- **VLAN Management** harus ada (`mgmt_vlan`, contoh: 111)
- **ip-host 2** harus dapat IP DHCP dari VLAN tersebut
- **veip 1** sudah di-bind ke switch_0/1

### Perintah

```bash
veip 1 port udp 1232 host 2       # VEIP endpoint, UDP 1232, pakai ip-host 2
tr069-mgmt 1 state unlock         # Aktifkan TR-069 management
tr069-mgmt 1 acs http://172.10.10.254:7547  # URL ACS (CWMP endpoint)
tr069-mgmt 1 tag pri 2 vlan 111   # Tag paket TR-069 dengan VLAN 111, priority 2
```

**Dengan credentials ACS (jika ACS memerlukan basic auth):**
```bash
tr069-mgmt 1 acs http://acs.server:7547 validate basic username user password pass
```

### Alur Kerja TR-069

```
ONU (ip-host 2) → DHCP → IP di VLAN 111
ONU → Koneksi TCP ke ACS URL (CWMP port 7547)
ACS → Kirim task: GetParameterValues, SetParameterValues, dst.
GenieACS → ONU terlihat sebagai device baru
```

> **Penting**: Setelah register, ONU akan menghubungi ACS dalam 1-3 menit pertama (bootstrap). Jika gagal, cek:
> 1. OLT `Config state: success` (bukan `fail`)
> 2. ip-host 2 dapat IP di VLAN management
> 3. Routing dari VLAN 111 ke IP ACS ada

---

## 8. Unregister ONU

```bash
configure terminal
interface gpon-olt_1/1/1
  no onu 19
exit
exit
write
```

> `no onu {id}` menghapus whitelist SN dan semua konfigurasi terkait ONU tersebut (tcont, gemport, service-port, pon-onu-mng).

---

## 9. Monitoring via SNMP

### SNMP Community Default ZTE C320

```
Read-Only (RO): combro   (atau baca dari: show running-config | include snmp-server)
Read-Write (RW): combrow
```

### OID Penting

#### Informasi Sistem

| OID | Deskripsi |
|-----|-----------|
| `1.3.6.1.2.1.1.1.0` | sysDescr |
| `1.3.6.1.2.1.1.5.0` | sysName |
| `1.3.6.1.4.1.3902.1015.2.1.1.1.0` | Product Name |
| `1.3.6.1.4.1.3902.1015.2.1.1.4.0` | Software Version |
| `1.3.6.1.4.1.3902.1015.2.1.1.5.0` | Hardware Version |

#### Board / Kartu

| OID | Deskripsi |
|-----|-----------|
| `1.3.6.1.4.1.3902.1015.2.1.3.3.1.2` | Board Type (GTGH, HUTQ, dll) |
| `1.3.6.1.4.1.3902.1015.2.1.3.3.1.4` | Board Oper State |
| `1.3.6.1.4.1.3902.1015.2.1.3.3.1.7` | Board PON Port Count |
| `1.3.6.1.4.1.3902.1015.2.1.3.3.1.8` | Board Uplink Port Count |

#### ONU Table (index: ponIfIndex.onuId)

| OID | Deskripsi |
|-----|-----------|
| `1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.1` | ONU Type (STRING "HG8245H") |
| `1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.2` | ONU Name |
| `1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.6` | ONU Serial Number (binary) |
| `1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.11` | Run Status (1=online, 2=offline, 3=LOS) |
| `1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.14` | Software Version |
| `1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.20` | Distance (meter) |

#### Status Run (kolom 11)

| Nilai | Status |
|-------|--------|
| 1 | online |
| 2 | offline |
| 3 | LOS (Loss of Signal) |
| 4 | dying_gasp |
| 5 | power_off |

#### Unconfigured ONU

| OID | Deskripsi |
|-----|-----------|
| `1.3.6.1.4.1.3902.1082.500.10.2.3.5.1.2` | Serial Number ONU belum dikonfigurasi |

#### Decode ponIfIndex

Pada ZTE C320, index PON port di SNMP dikodekan dalam satu integer besar:

```
ponIfIndex = rack*65536 + slot*256 + port + base_offset
```

Cara decode (dari kode):
```php
$slot = (int)(($ponIfIndex - $base) / 256) % 256;
$port = ($ponIfIndex - $base) % 256;
```

> Alternatif lebih mudah: gunakan CLI `show gpon onu summary` dan parse output text.

#### Decode Serial Number Binary

Serial ZTE di SNMP dikirim dalam format binary:
- 4 byte pertama = vendor ASCII (contoh: `HWTC` = Huawei)
- 4 byte berikutnya = serial hex (contoh: `6ED42F9A`)

```php
$vendor = substr($raw, 0, 4);            // "HWTC"
$serial = strtoupper(bin2hex(substr($raw, 4, 4))); // "6ED42F9A"
$fullSerial = $vendor . $serial;          // "HWTC6ED42F9A"
```

---

## 10. Monitoring via CLI

### Optical Power

```bash
# OLT-side RX power semua ONU di satu port
show pon power olt-rx gpon-olt_1/1/1

# ONU-side RX power semua ONU di satu port
show pon power onu-rx gpon-olt_1/1/1

# Detail power satu ONU (upstream + downstream + jarak)
show pon power attenuation gpon-onu_1/1/1:19
```

Output attenuation:
```
Direction  OLT-Side          ONU-Side          Loss
up         Rx:-26.347(dbm)   Tx:2.427(dbm)     28.774(dB)   ← OLT RX, ONU TX
down       Tx:6.983(dbm)     Rx:-20.606(dbm)   27.589(dB)   ← OLT TX, ONU RX
```

### Status ONU

```bash
show gpon onu detail-info gpon-onu_1/1/1:19
```

Field penting dalam output:
- `Config state: success/fail` — **fail = ada masalah profile/config**
- `Phase state: working/LOS` — `LOS` = ONU tidak ada cahaya/offline
- `Line Profile: N/A` — Normal pada C320 yang tidak pakai profile line terpusat

### Running Config ONU

```bash
show running-config interface gpon-onu_1/1/1:19
```

Contoh output (ONU terkonfigurasi dengan benar):
```
interface gpon-onu_1/1/1:20
  name tes2
  tcont 1 profile SMARTOLT-1G-UP
  gemport 1 tcont 1
  gemport 1 traffic-limit downstream SMARTOLT-1G-DOWN
  service-port 1 vport 1 user-vlan 335 vlan 335
```

### Statistik Traffic ONU

```bash
show interface gpon-onu_1/1/1:19 counters
```

---

## 11. Troubleshooting & Config State

### Config state: fail

Penyebab paling umum:

| Penyebab | Gejala | Solusi |
|----------|--------|--------|
| Profile TCONT tidak ada | `tcont 1 profile default` + profile "default" = Fixed 10M | Ganti ke profile valid: `tcont 1 profile SMARTOLT-1G-UP` |
| Nama ONU mengandung karakter khusus | Error saat set `name` | Hanya gunakan `A-Za-z0-9._-` |
| ONU Type salah | `Not support this ONU` | Ganti ke `ALL` atau type yang sesuai |
| VLAN tidak ada di OLT | Service-port error | Buat VLAN dulu di OLT |

**Fix Config state: fail (contoh — profile salah):**
```bash
configure terminal
interface gpon-onu_1/1/1:19
  tcont 1 profile SMARTOLT-1G-UP
exit
exit
write
```

### Phase state: LOS

ONU tidak terhubung secara fisik (fiber putus, SFP rusak, ONU mati). Tidak ada yang bisa dilakukan dari software.

### ONU Tidak Muncul di ACS

Cek urutan:
1. `Config state: success` → kalau `fail`, perbaiki dulu
2. `Phase state: working` → ONU online
3. ip-host 2 dapat IP DHCP di VLAN management → cek DHCP log
4. Routing dari VLAN management ke IP ACS ada → cek routing
5. ACS URL benar dan port 7547 bisa diakses dari network ONU

### Verifikasi Running Config

Setelah register, baca running config dan cek:
- [ ] `name` sesuai
- [ ] `tcont 1 profile NAMA-VALID` (bukan `default` jika ada risiko)
- [ ] `gemport 1 tcont 1`
- [ ] `service-port 1 vport 1 user-vlan {VLAN} vlan {VLAN}`
- [ ] `tcont 2` dan `service-port 2` ada jika pakai management VLAN

---

## 12. Referensi OID ZTE

### Traffic Statistics

| OID | Deskripsi |
|-----|-----------|
| `1.3.6.1.4.1.3902.1082.500.10.2.3.10.1.1` | ONU In Octets (RX dari ONU) |
| `1.3.6.1.4.1.3902.1082.500.10.2.3.10.1.2` | ONU Out Octets (TX ke ONU) |

### PON Port Optical (OLT-side SFP)

| OID | Deskripsi |
|-----|-----------|
| `1.3.6.1.4.1.3902.1082.500.10.2.2.1.1.10` | OLT PON TX Power |
| `1.3.6.1.4.1.3902.1082.500.10.2.2.1.1.11` | OLT PON RX Power |
| `1.3.6.1.4.1.3902.1082.500.10.2.2.1.1.12` | Temperatur |
| `1.3.6.1.4.1.3902.1082.500.10.2.2.1.1.13` | Voltage |
| `1.3.6.1.4.1.3902.1082.500.10.2.2.1.1.14` | Bias Current |

> **Catatan**: OID DDM untuk per-ONU (`zxAnGponOnuOpticalDdmTable`) tidak reliable di firmware V2.1.0. Gunakan CLI `show pon power attenuation` sebagai gantinya.

### VLAN (Q-BRIDGE-MIB Standard)

| OID | Deskripsi |
|-----|-----------|
| `1.3.6.1.2.1.17.7.1.4.3.1.1` | VLAN Name |
| `1.3.6.1.2.1.17.7.1.4.3.1.2` | Egress Ports (bitmask) |
| `1.3.6.1.2.1.17.7.1.4.3.1.4` | Untagged Ports (bitmask) |
| `1.3.6.1.2.1.17.7.1.4.3.1.5` | Row Status |
| `1.3.6.1.2.1.17.7.1.1.4.0` | Jumlah VLAN |

---

## 13. Ringkasan Parameter Input Provisioning

Berikut seluruh parameter yang dibutuhkan untuk mem-provision satu ONU lengkap:

### Parameter Wajib

| Parameter | Tipe | Contoh | Keterangan |
|-----------|------|--------|------------|
| `serial_number` | string | `HWTC6ED42F9A` | 12 char: 4 vendor + 8 hex |
| `slot` | int | `1` | Slot kartu GPON |
| `port` | int | `1` | Port PON di kartu |
| `name` | string | `omah-budi` | Hanya `A-Za-z0-9._-`, max 64 |
| `onu_type` | string | `HG8245H` | Model ONU atau `ALL` |
| `vlan` | int | `335` | VLAN internet pelanggan |
| `line_profile` (tcont profile) | string | `SMARTOLT-1G-UP` | Harus sudah ada di OLT |
| `traffic_profile` | string | `SMARTOLT-1G-DOWN` | Harus sudah ada di OLT |

### Parameter Opsional tapi Sangat Dianjurkan

| Parameter | Tipe | Contoh | Keterangan |
|-----------|------|--------|------------|
| `mgmt_vlan` | int | `111` | VLAN management/TR-069 |
| `onu_id` | int | `19` | Auto-assign jika kosong |
| `tcont_id` | int | `1` | Default: 1 |
| `gem_port` | int | `1` | Default: 1 |

### Parameter ACS/TR-069 (jika pakai GenieACS)

| Parameter | Tipe | Contoh | Keterangan |
|-----------|------|--------|------------|
| `acs_url` | string | `http://172.10.10.254:7547` | CWMP endpoint |
| `acs_username` | string | — | Opsional, basic auth |
| `acs_password` | string | — | Opsional, basic auth |

### Parameter PPPoE (jika PPPoE dilakukan di ONU)

| Parameter | Tipe | Contoh | Keterangan |
|-----------|------|--------|------------|
| `pppoe_username` | string | `hisyam` | Username PPPoE |
| `pppoe_password` | string | `password` | Password PPPoE |

> **Catatan**: PPPoE di ONU (dikonfigurasi via pon-onu-mng) = ONU yang melakukan dial PPPoE langsung. Jika pelanggan punya router yang melakukan dial PPPoE sendiri, parameter ini tidak perlu diisi — cukup pastikan VLAN internet pass-through dari ONU ke router pelanggan.

---

## Contoh Lengkap — Semua Perintah Sekaligus

```bash
# ── Tahap 2: Register SN ──────────────────────────────────────────
configure terminal
interface gpon-olt_1/1/1
  onu 19 type HG8245H sn HWTC6ED42F9A
exit

# ── Tahap 3: Interface ONU (OLT Layer) ───────────────────────────
interface gpon-onu_1/1/1:19
  name omah-budi
  tcont 1 profile SMARTOLT-1G-UP
  gemport 1 tcont 1
  gemport 1 traffic-limit downstream SMARTOLT-1G-DOWN
  service-port 1 vport 1 user-vlan 335 vlan 335
  tcont 2 profile SMARTOLT-VOIPMNG-10M
  gemport 2 tcont 2
  gemport 2 traffic-limit downstream SMARTOLT-VOIPMNG-10M
  service-port 2 vport 2 user-vlan 111 vlan 111
exit

# ── Tunggu ONU sync (8 detik) ─────────────────────────────────────

# ── Tahap 4: pon-onu-mng (OMCI Layer) ────────────────────────────
pon-onu-mng gpon-onu_1/1/1:19
  voip protocol sip
  flow 2 switch switch_0/1
  flow mode 1 tag-filter vlan-filter untag-filter discard
  flow mode 2 tag-filter vlan-filter untag-filter discard
  flow 1 pri 0 vlan 335
  flow 2 pri 2 vlan 111
  gemport 1 flow 1
  gemport 2 flow 2
  switchport-bind switch_0/1 iphost 1
  switchport-bind switch_0/1 iphost 2
  switchport-bind switch_0/1 veip 1
  ip-host 2 dhcp-enable enable ping-response enable traceroute-response enable
  vlan-filter-mode iphost 1 tag-filter vlan-filter untag-filter discard
  vlan-filter-mode iphost 2 tag-filter vlan-filter untag-filter discard
  vlan-filter iphost 1 pri 0 vlan 335
  vlan-filter iphost 2 pri 2 vlan 111
  dhcp-ip ethuni eth_0/1 from-onu
  dhcp-ip ethuni eth_0/2 from-onu
  dhcp-ip ethuni eth_0/3 from-onu
  dhcp-ip ethuni eth_0/4 from-onu
  veip 1 port udp 1232 host 2
  tr069-mgmt 1 state unlock
  tr069-mgmt 1 acs http://172.10.10.254:7547
  tr069-mgmt 1 tag pri 2 vlan 111
  security-mgmt 998 state enable mode forward ingress-type lan protocol web https
  security-mgmt 999 state enable ingress-type lan protocol ftp telnet ssh snmp tr069
exit

# ── Tahap 5: Simpan ───────────────────────────────────────────────
exit
write

# ── Tahap 6: Verifikasi ──────────────────────────────────────────
show running-config interface gpon-onu_1/1/1:19
show gpon onu detail-info gpon-onu_1/1/1:19
```
