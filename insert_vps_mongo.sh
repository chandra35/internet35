#!/bin/bash
# Insert/update getTxPower and getWanStatus VirtualParameters in GenieACS MongoDB
# Run on: VM 254 (GenieACS server)

MONGO_DB="genieacs"

echo "Inserting getTxPower VP..."
mongo --quiet $MONGO_DB <<'ENDMONGO'
db.virtualParameters.updateOne(
  { _id: "getTxPower" },
  { $set: { script: "// TX Optical Power\nlet m = \"N/A\";\nlet zte = declare(\"InternetGatewayDevice.WANDevice.*.X_ZTE-COM_WANPONInterfaceConfig.TXPower\", {value: Date.now()});\nlet huawei = declare(\"InternetGatewayDevice.WANDevice.*.X_GponInterafceConfig.TXPower\", {value: Date.now()});\nlet fiberhome = declare(\"InternetGatewayDevice.WANDevice.*.X_FH_GponInterfaceConfig.TXPower\", {value: Date.now()});\nif (zte.size) {\n  let val = zte.value[0];\n  if (typeof val !== \"undefined\" && val !== \"\") m = val;\n} else if (huawei.size) {\n  for (let p of huawei) {\n    if (p.value[0]) { m = p.value[0]; break; }\n  }\n} else if (fiberhome.size) {\n  for (let p of fiberhome) {\n    if (p.value[0]) { m = p.value[0]; break; }\n  }\n}\nreturn {writable: false, value: [m, \"xsd:string\"]};" } },
  { upsert: true }
)
ENDMONGO

echo "Inserting getWanStatus VP..."
mongo --quiet $MONGO_DB <<'ENDMONGO'
db.virtualParameters.updateOne(
  { _id: "getWanStatus" },
  { $set: { script: "// WAN PPPoE Connection Status\nlet result = \"Unknown\";\nlet keys = [\n  \"InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.ConnectionStatus\",\n  \"InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANPPPConnection.1.ConnectionStatus\"\n];\nfor (let i = 0; i < keys.length; i++) {\n  let d = declare(keys[i], {value: Date.now()});\n  if (d.size) {\n    for (let p of d) {\n      if (p.value && p.value[0]) { result = p.value[0]; break; }\n    }\n    if (result !== \"Unknown\") break;\n  }\n}\nreturn {writable: false, value: [result, \"xsd:string\"]};" } },
  { upsert: true }
)
ENDMONGO

echo ""
echo "Verifying:"
mongo --quiet $MONGO_DB <<'ENDMONGO'
db.virtualParameters.find({_id: {$in: ["getTxPower", "getWanStatus"]}}, {_id: 1}).forEach(function(d) { print("  OK: " + d._id); })
ENDMONGO
