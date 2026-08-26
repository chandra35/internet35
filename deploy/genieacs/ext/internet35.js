/**
 * GenieACS Extension: internet35
 *
 * Dipakai oleh bootstrap provision script untuk fetch konfigurasi
 * provisioning (PPPoE + WLAN) dari billing system Internet35.
 *
 * File ini harus diletakkan di /opt/genieacs/ext/internet35.js
 * EXT_DIR dikonfigurasi di /opt/genieacs/genieacs.env
 *
 * GenieACS extension function signature:
 *   exports.funcName = function(args, callback) { callback(fault, result); }
 *   fault: null | {code, message}
 *   result: nilai yang dikembalikan ke provision script via ext()
 */

"use strict";

const http = require("http");

// URL dan key disesuaikan dengan deployment. Nilai default untuk VM internal.
const APP_HOST = "172.16.2.4";
const APP_PORT = 80;
// Key ini HARUS sama dengan GENIEACS_PROVISION_KEY di .env Laravel
const ACS_KEY  = process.env.INTERNET35_PROVISION_KEY || "";

/**
 * Fetch provision data untuk ONU berdasarkan serial number.
 *
 * Dipanggil dari provision script sebagai:
 *   const cfg = ext("internet35", "getProvision", serial);
 *
 * Return value:
 *   { found: false }                         — ONU tidak ada di DB
 *   { found: true, has_data: false, ... }    — ONU ada tapi belum dikonfigurasi
 *   { found: true, has_data: true,           — siap di-push
 *     pppoe_username, pppoe_password,
 *     vlan, wifi_ssid, wifi_password }
 *
 * Jika terjadi error HTTP/network, return null (provision script harus handle null).
 */
exports.getProvision = function(args, callback) {
  const serial = String(args[0] || "").trim();
  if (!serial) {
    return callback(null, null);
  }

  const path = "/api/acs/provision/" + encodeURIComponent(serial);

  const options = {
    hostname: APP_HOST,
    port: APP_PORT,
    path: path,
    method: "GET",
    headers: {
      "X-ACS-Key": ACS_KEY,
      "Accept": "application/json",
    },
    // Timeout 8 detik — kalau app tidak respond, provision tetap jalan tanpa config
    timeout: 8000,
  };

  const req = http.request(options, function(res) {
    let raw = "";
    res.setEncoding("utf8");
    res.on("data", function(chunk) { raw += chunk; });
    res.on("end", function() {
      if (res.statusCode === 401) {
        // Jangan retry — key salah
        return callback({ code: "internet35_auth", message: "X-ACS-Key rejected by billing app" });
      }
      try {
        const data = JSON.parse(raw);
        callback(null, data);
      } catch (e) {
        callback({ code: "internet35_parse", message: "Invalid JSON from billing app: " + e.message });
      }
    });
  });

  req.on("timeout", function() {
    req.destroy();
    // Timeout bukan fatal — provision jalan tanpa push
    callback(null, null);
  });

  req.on("error", function(e) {
    // Network error — provision jalan tanpa push
    callback(null, null);
  });

  req.end();
};
