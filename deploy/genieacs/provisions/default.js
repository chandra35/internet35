/**
 * GenieACS Provision: default
 *
 * Dipicu oleh preset "default" pada setiap Inform (periodic, boot, dll).
 *
 * Tugas:
 * Auto-provision WAN PPPoE + WLAN jika belum ada -- menangani kasus
 * factory reset yang tidak mengirim "0 BOOTSTRAP" (Huawei via OMCI OLT).
 * Kondisi: WANPPPConnectionNumberOfEntries == 0 DAN data tersedia di billing.
 *
 * PENTING: Tidak push config setiap inform -- hanya ketika belum ada PPPoE.
 */

// DeviceID.SerialNumber di GenieACS = hex-encoded bytes, misal "48575443840472AE".
// Untuk Huawei GPON: 4 byte pertama = ASCII vendor ID, 4 byte terakhir = hex unit serial.
// Konversi ke format billing: "48575443" + "840472AE" → "HWTC" + "840472AE" = "HWTC840472AE"
var rawSerial = declare("DeviceID.SerialNumber", { value: 1 }).value[0] || "";
var brand     = declare("DeviceID.Manufacturer", { value: 1 }).value[0] || "";

// Konversi hex serial ke format billing (hanya untuk Huawei GPON format)
var serial = rawSerial;
if (rawSerial.length === 16 && /^[0-9A-Fa-f]+$/.test(rawSerial)) {
  var vendorHex  = rawSerial.substr(0, 8);
  var vendorText = "";
  for (var _i = 0; _i < 8; _i += 2) {
    vendorText += String.fromCharCode(parseInt(vendorHex.substr(_i, 2), 16));
  }
  serial = vendorText + rawSerial.substr(8).toUpperCase();
}

// Hanya untuk Huawei
if (serial && brand.indexOf("Huawei") !== -1) {

  // --- Always read: WLAN SSID bawaan ONU (agar tampil di billing dashboard) ---
  declare("InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID",   { value: 1 });
  declare("InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.Enable", { value: 1 });
  declare("InternetGatewayDevice.LANDevice.1.WLANConfiguration.5.SSID",   { value: 1 });
  declare("InternetGatewayDevice.LANDevice.1.WLANConfiguration.5.Enable", { value: 1 });

  // --- Always read: WAN MGMT VLAN (WANIPConnection di WANConnectionDevice.2) ---
  declare("InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANIPConnection.1.X_HW_VLAN",        { value: 1 });
  declare("InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANIPConnection.1.Name",             { value: 1 });
  declare("InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANIPConnection.1.ConnectionType",   { value: 1 });
  declare("InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANIPConnection.1.ExternalIPAddress",{ value: 1 });

  // Cek apakah WANPPPConnection sudah ada
  // NumberOfEntries = 0 berarti belum ada instance PPPoE
  var numPPPoE = declare(
    "InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnectionNumberOfEntries",
    { value: 1 }
  ).value[0];

  if (numPPPoE === 0 || numPPPoE === "0" || numPPPoE === null || numPPPoE === undefined) {

    // Tidak ada PPPoE -- fetch provision data dari billing app
    var cfg = null;
    try {
      cfg = ext("internet35", "getProvision", serial);
    } catch (e) {
      cfg = null;
    }

    if (cfg && cfg.found && cfg.has_data && cfg.pppoe_username && cfg.vlan) {

      var n = Date.now();
      var wanBase =
        "InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection";

      // Buat 1 instance PPPoE (addObject)
      declare(wanBase, null, { path: 1 });

      // Set parameter PPPoE
      declare(wanBase + ".*.Enable",           { value: n }, { value: true });
      declare(wanBase + ".*.ConnectionType",   { value: n }, { value: "PPPoE_Routed" });
      declare(wanBase + ".*.NATEnabled",       { value: n }, { value: true });
      declare(wanBase + ".*.Name",             { value: n }, { value: "PPPoE_WAN" });
      declare(wanBase + ".*.Username",         { value: n }, { value: cfg.pppoe_username });
      declare(wanBase + ".*.Password",         { value: n }, { value: cfg.pppoe_password || "" });
      declare(wanBase + ".*.X_HW_VLAN",        { value: n }, { value: cfg.vlan });
      declare(wanBase + ".*.X_HW_SERVICELIST", { value: n }, { value: "INTERNET" });

      // WLAN -- hanya jika SSID tersedia
      if (cfg.wifi_ssid) {
        var wlanBase = "InternetGatewayDevice.LANDevice.1.WLANConfiguration.1";
        declare(wlanBase + ".Enable",                        { value: n }, { value: true });
        declare(wlanBase + ".SSID",                          { value: n }, { value: cfg.wifi_ssid });
        declare(wlanBase + ".PreSharedKey.1.KeyPassphrase",  { value: n }, { value: cfg.wifi_password || "" });
      }
    }
  }
}
