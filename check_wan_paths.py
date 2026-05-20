#!/usr/bin/env python3
"""
Check WAN parameter paths and brands of all GenieACS devices.
Run on VM 254: python3 /tmp/check_wan_paths.py
"""
import json, urllib.request

NBI = "http://127.0.0.1:7557"

# Fetch all devices with WAN + DeviceInfo projection
projection = ",".join([
    "_id",
    "InternetGatewayDevice.DeviceInfo.Manufacturer",
    "InternetGatewayDevice.DeviceInfo.ModelName",
    "InternetGatewayDevice.WANDevice",
])

url = f"{NBI}/devices?projection={projection}"
with urllib.request.urlopen(url, timeout=30) as r:
    devices = json.load(r)

print(f"Total devices: {len(devices)}\n")

brand_stats = {}
wan_path_samples = {}

for d in devices:
    dev_id = d.get("_id", "?")
    igd = d.get("InternetGatewayDevice", {})
    di  = igd.get("DeviceInfo", {})
    mfr = (di.get("Manufacturer") or {}).get("_value", "Unknown")
    mdl = (di.get("ModelName") or {}).get("_value", "?")

    # Count brands
    brand_stats[mfr] = brand_stats.get(mfr, 0) + 1

    # Explore WANDevice paths
    wan = igd.get("WANDevice", {})
    for wan_idx, wan_dev in wan.items():
        if not isinstance(wan_dev, dict):
            continue
        wcd = wan_dev.get("WANConnectionDevice", {})
        for wcd_idx, conn_dev in wcd.items():
            if not isinstance(conn_dev, dict):
                continue
            # Check WANPPPConnection
            ppp = conn_dev.get("WANPPPConnection", {})
            for ppp_idx, ppp_entry in ppp.items():
                if not isinstance(ppp_entry, dict):
                    continue
                status = (ppp_entry.get("ConnectionStatus") or {}).get("_value")
                path = f"IGD.WANDevice.{wan_idx}.WANConnectionDevice.{wcd_idx}.WANPPPConnection.{ppp_idx}.ConnectionStatus"
                if mfr not in wan_path_samples:
                    wan_path_samples[mfr] = []
                if len(wan_path_samples[mfr]) < 2:
                    wan_path_samples[mfr].append({
                        "device": dev_id[:40],
                        "model": mdl,
                        "path": path,
                        "status": status,
                    })
            # Check WANIPConnection
            ip_conn = conn_dev.get("WANIPConnection", {})
            for ip_idx, ip_entry in ip_conn.items():
                if not isinstance(ip_entry, dict):
                    continue
                status = (ip_entry.get("ConnectionStatus") or {}).get("_value")
                path = f"IGD.WANDevice.{wan_idx}.WANConnectionDevice.{wcd_idx}.WANIPConnection.{ip_idx}.ConnectionStatus"
                key = f"{mfr}_IP"
                if key not in wan_path_samples:
                    wan_path_samples[key] = []
                if len(wan_path_samples[key]) < 2:
                    wan_path_samples[key].append({
                        "device": dev_id[:40],
                        "model": mdl,
                        "path": path,
                        "status": status,
                    })

print("=== Brands ===")
for brand, count in sorted(brand_stats.items(), key=lambda x: -x[1]):
    print(f"  {brand:30s} : {count} devices")

print("\n=== WAN Path Samples per Brand ===")
for brand, samples in sorted(wan_path_samples.items()):
    print(f"\n[{brand}]")
    for s in samples:
        print(f"  Device: {s['device']}")
        print(f"  Model : {s['model']}")
        print(f"  Path  : {s['path']}")
        print(f"  Status: {s['status']}")
