#!/usr/bin/env python3
"""
Find TX power related OID paths in Huawei devices.
Run on VM 254: python3 /tmp/find_tx_paths.py
"""
import json, urllib.request

NBI = "http://127.0.0.1:7557"

# Fetch one device with full WANDevice tree to find TX power paths
url = f"{NBI}/devices?limit=1&projection=_id,InternetGatewayDevice.WANDevice"
with urllib.request.urlopen(url, timeout=30) as r:
    devices = json.load(r)

d = devices[0]
print("Device:", d.get("_id"))
print()

def walk(node, path="IGD", depth=0):
    if not isinstance(node, dict):
        return
    for k, v in sorted(node.items()):
        if k.startswith("_"):
            continue
        full_path = f"{path}.{k}"
        if isinstance(v, dict):
            val = v.get("_value")
            ts  = v.get("_timestamp")
            if val is not None or ts is not None:
                print(f"  {full_path} = {val}  (ts={ts is not None})")
            walk(v, full_path, depth+1)

igd = d.get("InternetGatewayDevice", {})
wan = igd.get("WANDevice", {})
walk(wan, "WANDevice")
