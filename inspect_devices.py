#!/usr/bin/env python3
"""
Deep inspect of WAN paths and DeviceInfo for all brands.
Run on VM 254: python3 /tmp/inspect_devices.py
"""
import json, urllib.request

NBI = "http://127.0.0.1:7557"

# 1. Fetch full DeviceInfo from 3 devices
url = f"{NBI}/devices?limit=3&projection=_id,InternetGatewayDevice.DeviceInfo,InternetGatewayDevice.WANDevice.1.WANConnectionDevice"
with urllib.request.urlopen(url, timeout=30) as r:
    devices = json.load(r)

for d in devices:
    print("=" * 60)
    print("Device:", d.get("_id"))
    igd = d.get("InternetGatewayDevice", {})
    di = igd.get("DeviceInfo", {})

    print("\n--- DeviceInfo fields with values ---")
    for k, v in sorted(di.items()):
        if isinstance(v, dict) and "_value" in v and v["_value"] is not None:
            print(f"  {k}: {v['_value']}")

    print("\n--- WANDevice.1.WANConnectionDevice structure ---")
    wcd = igd.get("WANDevice", {}).get("1", {}).get("WANConnectionDevice", {})
    for wcd_idx, wcd_val in wcd.items():
        if not isinstance(wcd_val, dict):
            continue
        print(f"  WANConnectionDevice.{wcd_idx}:")
        for conn_type in ["WANPPPConnection", "WANIPConnection"]:
            conns = wcd_val.get(conn_type, {})
            for conn_idx, conn_val in conns.items():
                if not isinstance(conn_val, dict):
                    continue
                status = (conn_val.get("ConnectionStatus") or {}).get("_value")
                ext_ip = (conn_val.get("ExternalIPAddress") or {}).get("_value")
                print(f"    {conn_type}.{conn_idx}:")
                print(f"      ConnectionStatus = {status}")
                print(f"      ExternalIPAddress = {ext_ip}")

print("\n" + "=" * 60)

# 2. Also fetch VirtualParameters samples
print("\n--- VirtualParameters sample ---")
url2 = f"{NBI}/devices?limit=5&projection=_id,VirtualParameters"
with urllib.request.urlopen(url2, timeout=30) as r:
    devices2 = json.load(r)

for d in devices2:
    vp = d.get("VirtualParameters", {})
    has_vals = {k: v.get("_value") for k, v in vp.items() if isinstance(v, dict) and v.get("_value") not in (None, "N/A", "Unknown", "")}
    if has_vals:
        print(f"\nDevice: {d.get('_id')}")
        for k, v in has_vals.items():
            print(f"  {k} = {v}")
