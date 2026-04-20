# TR-069 ACS Parameter Reference

> Dokumen ini mencatat mapping OID/parameter TR-069 per brand/model ONT yang digunakan di aplikasi internet35.
> Setiap brand memiliki path OID yang berbeda. Selalu cek via GenieACS NBI sebelum mengimplementasikan brand baru.

---

## Cara membaca dokumen ini

| Kolom | Arti |
|-------|------|
| **Field / Key** | Nama field internal di `GenieAcsService.php` |
| **Full OID Path** | Path lengkap di data model TR-069 perangkat |
| **Type** | Tipe data nilai (`bool` = "true"/"false" string, `int`, `string`) |
| **Catatan** | Perilaku khusus / nilai yang mungkin |

---

## 1. Huawei HG8245H (EchoLife)

- **Device ID format**: `00259E-HG8245H-{serial}`
- **Data model**: `InternetGatewayDevice.*` (TR-098)
- **Diuji dengan GenieACS NBI** pada perangkat nyata, April 2025

### 1.1 ACS / Management

| Field | Full OID Path | Type | Catatan |
|-------|--------------|------|---------|
| acs_url | `InternetGatewayDevice.ManagementServer.URL` | string | URL GenieACS CWMP |
| acs_username | `InternetGatewayDevice.ManagementServer.Username` | string | |
| periodic_inform | `InternetGatewayDevice.ManagementServer.PeriodicInformEnable` | bool | |
| periodic_interval | `InternetGatewayDevice.ManagementServer.PeriodicInformInterval` | int | Satuan detik |
| connection_request_url | `InternetGatewayDevice.ManagementServer.ConnectionRequestURL` | string | Read-only |

### 1.2 WAN

| Field | Full OID Path | Type | Catatan |
|-------|--------------|------|---------|
| wan_list | `InternetGatewayDevice.WANDevice.1.WANConnectionDevice.{i}.WANIPConnection.{i}.*` | - | Index dimulai dari 1 |
| wan_ip | `...WANIPConnection.{i}.ExternalIPAddress` | string | |
| wan_gateway | `...WANIPConnection.{i}.DefaultGateway` | string | |
| wan_dns | `...WANIPConnection.{i}.DNSServers` | string | Comma-separated |
| wan_status | `...WANIPConnection.{i}.ConnectionStatus` | string | `Connected` / `Disconnected` |
| wan_type | `...WANIPConnection.{i}.ConnectionType` | string | `IP_Routed`, `IP_Bridged` |
| wan_name | `...WANIPConnection.{i}.Name` | string | |
| wan_vlan_id | `InternetGatewayDevice.WANDevice.1.WANConnectionDevice.{i}.X_HW_VLAN_ID` | int | Huawei extension |
| wan_service_list | `...WANIPConnection.{i}.X_HW_ServiceList` | string | e.g. `INTERNET`, `TR069`, `VOIP` |
| wan_pppoe_user | `...WANPPPConnection.{i}.Username` | string | Untuk PPPoE |
| wan_pppoe_pass | `...WANPPPConnection.{i}.Password` | string | Write-only |

### 1.3 WiFi

| Field | Full OID Path | Type | Catatan |
|-------|--------------|------|---------|
| wifi_enable | `InternetGatewayDevice.LANDevice.1.WLANConfiguration.{i}.Enable` | bool | |
| wifi_ssid | `InternetGatewayDevice.LANDevice.1.WLANConfiguration.{i}.SSID` | string | |
| wifi_channel | `InternetGatewayDevice.LANDevice.1.WLANConfiguration.{i}.Channel` | int | 0 = auto |
| wifi_standard | `InternetGatewayDevice.LANDevice.1.WLANConfiguration.{i}.Standard` | string | `b`, `g`, `n`, `ac` |
| wifi_security_mode | `InternetGatewayDevice.LANDevice.1.WLANConfiguration.{i}.BeaconType` | string | `WPA2` dll |
| wifi_encryption | `InternetGatewayDevice.LANDevice.1.WLANConfiguration.{i}.WPAEncryptionModes` | string | `AESEncryption` |
| wifi_password | `InternetGatewayDevice.LANDevice.1.WLANConfiguration.{i}.PreSharedKey.1.PreSharedKey` | string | |
| wifi_tx_power | `InternetGatewayDevice.LANDevice.1.WLANConfiguration.{i}.TransmitPower` | int | % (25/50/75/100) |
| wifi_bandwidth | `InternetGatewayDevice.LANDevice.1.WLANConfiguration.{i}.X_HW_ChannelWidth` | string | `20M`, `40M`, `80M` |
| wifi_hidden | `InternetGatewayDevice.LANDevice.1.WLANConfiguration.{i}.SSIDAdvertisementEnabled` | bool | false = hidden |
| client_list | `InternetGatewayDevice.LANDevice.1.WLANConfiguration.{i}.AssociatedDevice.{j}.*` | - | Connected clients |
| client_mac | `...AssociatedDevice.{j}.AssociatedDeviceMACAddress` | string | |
| client_ip | `...AssociatedDevice.{j}.X_HW_IPAddress` | string | Huawei extension |
| client_hostname | `...AssociatedDevice.{j}.X_HW_HostName` | string | Huawei extension |
| client_rssi | `...AssociatedDevice.{j}.X_HW_RSSI` | int | dBm |

> **Catatan WiFi index**: HG8245H memiliki 2 interface — index `1` = 2.4GHz, index `5` = 5GHz (atau `2` tergantung firmware)

### 1.4 LAN / Ethernet Port

| Field | Full OID Path | Type | Catatan |
|-------|--------------|------|---------|
| port_list | `InternetGatewayDevice.LANDevice.1.LANEthernetInterfaceConfig.{i}.*` | - | |
| port_name | `...LANEthernetInterfaceConfig.{i}.Name` | string | `eth0:1`, `eth0:2`, dll |
| port_enable | `...LANEthernetInterfaceConfig.{i}.Enable` | bool | |
| port_status | `...LANEthernetInterfaceConfig.{i}.Status` | string | `Up` / `Down` / `NoMedia` |
| port_mac | `...LANEthernetInterfaceConfig.{i}.MACAddress` | string | |
| port_max_speed | `...LANEthernetInterfaceConfig.{i}.MaxBitRate` | int | Mbps |
| port_duplex | `...LANEthernetInterfaceConfig.{i}.DuplexMode` | string | `Full` / `Half` / `Auto` |
| port_hw_speed | `...LANEthernetInterfaceConfig.{i}.X_HW_ActualMaxBitRate` | int | Speed aktual — Huawei ext |
| port_hw_duplex | `...LANEthernetInterfaceConfig.{i}.X_HW_ActualDuplexMode` | string | Duplex aktual — Huawei ext |

### 1.5 DHCP Server

| Field | Full OID Path | Type | Catatan |
|-------|--------------|------|---------|
| dhcp_enable | `InternetGatewayDevice.LANDevice.1.LANHostConfigManagement.DHCPServerEnable` | bool | |
| dhcp_start | `InternetGatewayDevice.LANDevice.1.LANHostConfigManagement.MinAddress` | string | IP pertama pool |
| dhcp_end | `InternetGatewayDevice.LANDevice.1.LANHostConfigManagement.MaxAddress` | string | IP terakhir pool |
| dhcp_subnet | `InternetGatewayDevice.LANDevice.1.LANHostConfigManagement.SubnetMask` | string | |
| dhcp_gateway | `InternetGatewayDevice.LANDevice.1.LANHostConfigManagement.IPRouters` | string | |
| dhcp_dns | `InternetGatewayDevice.LANDevice.1.LANHostConfigManagement.DNSServers` | string | Comma-separated |
| dhcp_lease | `InternetGatewayDevice.LANDevice.1.LANHostConfigManagement.DHCPLeaseTime` | int | Detik; -1 = infinite |
| lan_ip | `InternetGatewayDevice.LANDevice.1.LANHostConfigManagement.IPInterface.1.IPInterfaceIPAddress` | string | IP LAN router |
| lan_clients | `InternetGatewayDevice.LANDevice.1.Hosts.Host.{i}.*` | - | List DHCP clients |
| client_mac | `...Host.{i}.MACAddress` | string | |
| client_ip | `...Host.{i}.IPAddress` | string | |
| client_hostname | `...Host.{i}.HostName` | string | |
| client_iface_type | `...Host.{i}.InterfaceType` | string | `802.11` (WiFi) / `Ethernet` (LAN) |
| client_active | `...Host.{i}.Active` | bool | |
| client_lease | `...Host.{i}.LeaseTimeRemaining` | int | Detik sisa lease |

### 1.6 Security — ACL (Remote Access Control)

Path prefix: `InternetGatewayDevice.X_HW_Security.AclServices`

| Field / Key | Full OID Path | Type | Catatan |
|-------------|--------------|------|---------|
| acl_ftp_lan | `...AclServices.FTPLanEnable` | bool | FTP dari LAN |
| acl_ftp_wan | `...AclServices.FTPWanEnable` | bool | FTP dari WAN |
| acl_http_lan | `...AclServices.HTTPLanEnable` | bool | Web UI dari LAN |
| acl_http_wan | `...AclServices.HTTPWanEnable` | bool | Web UI dari WAN |
| acl_ssh_lan | `...AclServices.SSHLanEnable` | bool | SSH dari LAN |
| acl_ssh_wan | `...AclServices.SSHWanEnable` | bool | SSH dari WAN |
| acl_samba_lan | `...AclServices.SamBaLanEnable` | bool | Samba dari LAN |
| acl_samba_wan | `...AclServices.SamBaWanEnable` | bool | Samba dari WAN |
| acl_telnet_lan | `...AclServices.TELNETLanEnable` | bool | Telnet dari LAN |
| acl_telnet_wan | `...AclServices.TELNETWanEnable` | bool | Telnet dari WAN |
| acl_icmp_echo | `InternetGatewayDevice.X_HW_Security.Dosfilter.IcmpEchoReplyEn` | bool | WAN ICMP Echo Reply |
| firewall_level | `InternetGatewayDevice.X_HW_Security.X_HW_FirewallLevel` | string | `Low`/`Middle`/`High`/`Custom` |

### 1.7 Security — CLI Service

| Field / Key | Full OID Path | Type | Catatan |
|-------------|--------------|------|---------|
| cli_ssh_enable | `InternetGatewayDevice.UserInterface.X_HW_CLISSHControl.Enable` | bool | SSH CLI service |
| cli_telnet_enable | `InternetGatewayDevice.UserInterface.X_HW_CLITelnetAccess.Access` | bool | Telnet CLI service |
| cli_telnet_wan | `InternetGatewayDevice.UserInterface.X_HW_CLITelnetAccess.X_HW_WanSecurityEnable` | bool | Akses Telnet dari WAN |
| cli_telnet_port | `InternetGatewayDevice.UserInterface.X_HW_CLITelnetAccess.TelnetPort` | int | Default: 23 |
| cli_username | `InternetGatewayDevice.UserInterface.X_HW_CLIUserInfo.1.Username` | string | Read-only |
| cli_password | `InternetGatewayDevice.UserInterface.X_HW_CLIUserInfo.1.Userpassword` | string | Write-only |

### 1.8 Security — Web UI Accounts

> Index `1` = Admin (level lebih tinggi), Index `2` = User/Support (level lebih rendah)
> Perlu diverifikasi per firmware — bisa berbalik urutan

| Field / Key | Full OID Path | Type | Catatan |
|-------------|--------------|------|---------|
| web_admin_enable | `InternetGatewayDevice.UserInterface.X_HW_WebUserInfo.1.Enable` | bool | |
| web_admin_username | `InternetGatewayDevice.UserInterface.X_HW_WebUserInfo.1.UserName` | string | Biasanya "telecomadmin" |
| web_admin_password | `InternetGatewayDevice.UserInterface.X_HW_WebUserInfo.1.Password` | string | Write-only |
| web_user_enable | `InternetGatewayDevice.UserInterface.X_HW_WebUserInfo.2.Enable` | bool | |
| web_user_username | `InternetGatewayDevice.UserInterface.X_HW_WebUserInfo.2.UserName` | string | Biasanya "admin" |
| web_user_password | `InternetGatewayDevice.UserInterface.X_HW_WebUserInfo.2.Password` | string | Write-only |

> **Nilai nyata dari perangkat (diverifikasi April 2025)**:
> - Index 1: Enable=true, UserName=`Admin` (bukan `telecomadmin` — tergantung firmware/konfigurasi ISP)
> - Index 2: Enable=true, UserName=`Support`

### 1.9 Firmware

| Field | Full OID Path | Type | Catatan |
|-------|--------------|------|---------|
| software_version | `InternetGatewayDevice.DeviceInfo.SoftwareVersion` | string | Versi firmware |
| hardware_version | `InternetGatewayDevice.DeviceInfo.HardwareVersion` | string | |
| model_name | `InternetGatewayDevice.DeviceInfo.ModelName` | string | |
| manufacturer | `InternetGatewayDevice.DeviceInfo.Manufacturer` | string | |
| serial_number | `InternetGatewayDevice.DeviceInfo.SerialNumber` | string | |
| uptime | `InternetGatewayDevice.DeviceInfo.UpTime` | int | Detik |

### 1.10 GenieACS — cara kirim parameter ke perangkat

```php
// Set single parameter (via NBI API)
// PUT /devices/{deviceId}/tasks
{
    "name": "setParameterValues",
    "parameterValues": [
        ["InternetGatewayDevice.X_HW_Security.AclServices.FTPLanEnable", "true", "xsd:boolean"],
        ["InternetGatewayDevice.X_HW_Security.AclServices.FTPWanEnable", "false", "xsd:boolean"]
    ],
    "connection_request": true  // langsung kirim ke perangkat tanpa menunggu inform
}

// xsd types yang umum dipakai:
// bool  -> "xsd:boolean"  (nilai: "true" / "false")
// int   -> "xsd:unsignedInt" atau "xsd:int"
// str   -> "xsd:string"
```

---

## 2. ZTE F670L / F660

> **Status**: Belum diimplementasikan di GenieAcsService. Catatan awal dari eksplorasi.

- **Data model**: `InternetGatewayDevice.*` (TR-098), mirip Huawei tapi path berbeda
- **Device ID format**: `{OUI}-{ModelName}-{Serial}`

### 2.1 WiFi (berbeda dengan Huawei)

| Field | Kemungkinan Path ZTE | Catatan |
|-------|---------------------|---------|
| wifi_ssid | `InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID` | Sama |
| wifi_password | `InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.PreSharedKey.1.KeyPassphrase` | **Berbeda!** ZTE pakai `KeyPassphrase`, bukan `PreSharedKey` |
| wifi_security | `InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.BasicEncryptionModes` | Path berbeda |

### 2.2 Security (perlu diverifikasi)

> ZTE umumnya **tidak** punya `X_HW_Security`. Path security-nya berbeda total.
> Perlu di-walk via GenieACS untuk menemukan OID yang benar.

---

## 3. TP-Link (Archer / XR series)

> **Status**: Belum diimplementasikan. Belum ada perangkat untuk diuji.

- **Data model**: Biasanya TR-181 (`Device.WiFi.*`, `Device.Ethernet.*`)
- Jika menggunakan TR-181, path **sangat berbeda** dari TR-098

### 3.1 WiFi TR-181

| Field | Path TR-181 | Catatan |
|-------|------------|---------|
| wifi_ssid | `Device.WiFi.SSID.{i}.SSID` | |
| wifi_enable | `Device.WiFi.Radio.{i}.Enable` | |
| wifi_password | `Device.WiFi.AccessPoint.{i}.Security.KeyPassphrase` | |
| wifi_security | `Device.WiFi.AccessPoint.{i}.Security.ModeEnabled` | `WPA2-Personal` dll |

---

## 4. Template untuk brand baru

Saat akan menambahkan brand baru, lakukan langkah berikut:

### 4.1 Cara explore OID via GenieACS NBI

```bash
# 1. Lihat semua parameter yang sudah diketahui GenieACS untuk device ini
curl "http://172.10.10.254:7557/devices/{deviceId}"

# 2. Filter bagian tertentu (contoh: Security)
curl "http://172.10.10.254:7557/devices/{deviceId}" | python -m json.tool | grep -i security

# 3. Trigger refresh (agar GenieACS fetch data terbaru dari device)
curl -X POST "http://172.10.10.254:7557/devices/{deviceId}/tasks" \
  -H "Content-Type: application/json" \
  -d '{"name":"refreshObject","objectName":"InternetGatewayDevice"}'
```

### 4.2 Template tabel parameter baru

```markdown
## X. Brand ModelName

- **Device ID format**: `{OUI}-{Model}-{Serial}`
- **Data model**: `InternetGatewayDevice.*` / `Device.*`

| Field / Key | Full OID Path | Type | Catatan |
|-------------|--------------|------|---------|
| ...         | ...          | ...  | ...     |
```

### 4.3 Checklist implementasi di GenieAcsService.php

- [ ] `getWifiInfo()` — baca SSID, password, channel, security
- [ ] `setWifiSettings()` — tulis SSID/password/enable
- [ ] `getLanInfo()` — baca port status, speed, duplex
- [ ] `getDhcpInfo()` — baca DHCP server config + client list
- [ ] `getSecurityInfo()` — baca ACL, firewall, CLI, web accounts
- [ ] `setSecuritySettings()` — tulis ACL toggles, passwords
- [ ] `getAcsInfo()` — baca ACS URL, interval

---

## Referensi

- [TR-069 Amendment 6 (Broadband Forum)](https://www.broadband-forum.org/pdfs/tr-069.pdf)
- [TR-098 Data Model](https://cwmp-data-models.broadband-forum.org/tr-098-1-8-0.html) — InternetGatewayDevice (lama, kebanyakan ONT pakai ini)
- [TR-181 Data Model](https://device-data-model.broadband-forum.org/BBF-TR-181-2-16-0-usp.html) — Device (baru, router modern)
- GenieACS NBI API: `http://{server}:7557/` — lihat [GenieACS docs](https://docs.genieacs.com/)
