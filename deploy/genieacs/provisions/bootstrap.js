/**
 * GenieACS Provision: bootstrap
 *
 * Dipicu oleh preset "bootstrap" saat event: 0 BOOTSTRAP
 * (factory reset, atau pertama kali device connect ke ACS).
 *
 * Tugas:
 * 1. Clear data model cache GenieACS supaya re-read semua parameter dari device
 * 2. Auto-provision WAN PPPoE + WLAN jika konfigurasi tersedia di billing system
 *
 * Auto-provision hanya dijalankan untuk device Huawei (satu-satunya brand
 * yang di-manage via TR-069 di setup ini). Brand lain: skip.
 *
 * CATATAN FIRMWARE HUAWEI:
 * - deleteObject pada WLANConfiguration → faultCode 9002 (tidak didukung firmware)
 * - configureWan via declare() berjalan di session yang sama dengan bootstrap
 * - WANConnectionDevice.1 sudah ada di factory config, PPPoE perlu dibuat baru
 */

// ── Clear cached data model ──────────────────────────────────────────────────
// Setelah clear(), GenieACS akan request ulang semua parameter dari device
// di session ini (GetParameterNames + GetParameterValues).
const now = Date.now();
clear("Device", now);
clear("InternetGatewayDevice", now);


// ── Auto-provision (Huawei only) ─────────────────────────────────────────────
// DeviceID.SerialNumber di GenieACS = hex-encoded bytes, misal "48575443840472AE".
// Untuk Huawei GPON: 4 byte pertama = ASCII vendor ID, 4 byte terakhir = hex unit serial.
// Konversi ke format billing: "48575443" → "HWTC", lalu gabung "840472AE" = "HWTC840472AE"
const rawSerial = declare("DeviceID.SerialNumber", { value: 1 }).value[0] || "";
const brand     = declare("DeviceID.Manufacturer", { value: 1 }).value[0] || "";

let serial = rawSerial;
if (rawSerial.length === 16 && /^[0-9A-Fa-f]+$/.test(rawSerial)) {
  const vendorHex  = rawSerial.substr(0, 8);
  let vendorText = "";
  for (let _i = 0; _i < 8; _i += 2) {
    vendorText += String.fromCharCode(parseInt(vendorHex.substr(_i, 2), 16));
  }
  serial = vendorText + rawSerial.substr(8).toUpperCase();
}

// Hanya jalankan untuk Huawei.
// Cek substring karena beberapa firmware kirim "Huawei Technologies Co., Ltd"
// dan beberapa kirim "Huawei".
if (serial && brand.indexOf("Huawei") !== -1) {

  let cfg = null;
  try {
    cfg = ext("internet35", "getProvision", serial);
  } catch (e) {
    // Jika extension error (billing app down, etc.), lewati auto-provision —
    // admin bisa push manual dari billing app. Jangan gagalkan bootstrap.
    cfg = null;
  }

  if (cfg && cfg.found && cfg.has_data &&
      cfg.pppoe_username && cfg.vlan) {

    const n = Date.now();

    // ── WAN PPPoE ────────────────────────────────────────────────────────────
    // Untuk Huawei setelah factory reset:
    // - WANDevice.1.WANConnectionDevice.1 sudah ada (DHCP management)
    // - WANPPPConnection belum ada → declare dengan {path:1} akan addObject
    const wanBase =
      "InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection";

    // Pastikan minimal 1 PPPoE connection ada (addObject jika belum ada)
    declare(wanBase, null, { path: 1 });

    // Set credentials + parameter pada semua PPPoE connection di path ini
    // (setelah factory reset harusnya hanya 1 instance)
    declare(wanBase + ".*.Enable",           { value: n }, { value: true });
    declare(wanBase + ".*.ConnectionType",   { value: n }, { value: "PPPoE_Routed" });
    declare(wanBase + ".*.NATEnabled",       { value: n }, { value: true });
    declare(wanBase + ".*.Name",             { value: n }, { value: "PPPoE_WAN" });
    declare(wanBase + ".*.Username",         { value: n }, { value: cfg.pppoe_username });
    declare(wanBase + ".*.Password",         { value: n }, { value: cfg.pppoe_password || "" });
    declare(wanBase + ".*.X_HW_VLAN",        { value: n }, { value: cfg.vlan });
    declare(wanBase + ".*.X_HW_SERVICELIST", { value: n }, { value: "INTERNET" });

    // ── WLAN ─────────────────────────────────────────────────────────────────
    // Hanya push jika SSID tersedia (admin sudah set di billing app).
    // Huawei HG8145X6-10: index 1 = radio 2.4GHz utama.
    if (cfg.wifi_ssid) {
      const wlanBase =
        "InternetGatewayDevice.LANDevice.1.WLANConfiguration.1";

      declare(wlanBase + ".Enable",
        { value: n }, { value: true });
      declare(wlanBase + ".SSID",
        { value: n }, { value: cfg.wifi_ssid });
      declare(wlanBase + ".PreSharedKey.1.KeyPassphrase",
        { value: n }, { value: cfg.wifi_password || "" });
    }
  }
}
