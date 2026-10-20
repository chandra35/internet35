import sys, json
d = json.load(sys.stdin)
wd = d.get("InternetGatewayDevice", {}).get("WANDevice", {})
for k1, v1 in wd.items():
    if k1.startswith("_"): continue
    print(f"WANDevice.{k1}:")
    if isinstance(v1, dict):
        wcd = v1.get("WANConnectionDevice", {})
        for k2, v2 in wcd.items():
            if k2.startswith("_"): continue
            print(f"  WANConnectionDevice.{k2}:")
            if isinstance(v2, dict):
                for k3, v3 in v2.items():
                    if k3.startswith("_"): continue
                    if isinstance(v3, dict):
                        print(f"    {k3}:")
                        for k4, v4 in v3.items():
                            if k4.startswith("_"): continue
                            if isinstance(v4, dict):
                                name_val = v4.get("Name", {}).get("_value", "")
                                conn_type = v4.get("ConnectionType", {}).get("_value", "")
                                enable = v4.get("Enable", {}).get("_value", "")
                                status = v4.get("ConnectionStatus", {}).get("_value", "")
                                username = v4.get("Username", {}).get("_value", "")
                                extip = v4.get("ExternalIPAddress", {}).get("_value", "")
                                print(f"      {k4}: Name={name_val}, Type={conn_type}, Enable={enable}, Status={status}, User={username}, IP={extip}")
