// Run: mongo genieacs insert_vps.js

var txPowerScript = [
  "// TX Optical Power",
  "let m = \"N/A\";",
  "let zte = declare(\"InternetGatewayDevice.WANDevice.*.X_ZTE-COM_WANPONInterfaceConfig.TXPower\", {value: Date.now()});",
  "let huawei = declare(\"InternetGatewayDevice.WANDevice.*.X_GponInterafceConfig.TXPower\", {value: Date.now()});",
  "let fiberhome = declare(\"InternetGatewayDevice.WANDevice.*.X_FH_GponInterfaceConfig.TXPower\", {value: Date.now()});",
  "if (zte.size) {",
  "  let val = zte.value[0];",
  "  if (typeof val !== \"undefined\" && val !== \"\") m = val;",
  "} else if (huawei.size) {",
  "  for (let p of huawei) {",
  "    if (p.value[0]) { m = p.value[0]; break; }",
  "  }",
  "} else if (fiberhome.size) {",
  "  for (let p of fiberhome) {",
  "    if (p.value[0]) { m = p.value[0]; break; }",
  "  }",
  "}",
  "return {writable: false, value: [m, \"xsd:string\"]};"
].join("\n");

var wanStatusScript = [
  "// WAN PPPoE Connection Status",
  "let result = \"Unknown\";",
  "let keys = [",
  "  \"InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.ConnectionStatus\",",
  "  \"InternetGatewayDevice.WANDevice.1.WANConnectionDevice.2.WANPPPConnection.1.ConnectionStatus\"",
  "];",
  "for (let i = 0; i < keys.length; i++) {",
  "  let d = declare(keys[i], {value: Date.now()});",
  "  if (d.size) {",
  "    for (let p of d) {",
  "      if (p.value && p.value[0]) { result = p.value[0]; break; }",
  "    }",
  "    if (result !== \"Unknown\") break;",
  "  }",
  "}",
  "return {writable: false, value: [result, \"xsd:string\"]};"
].join("\n");

print("Inserting getTxPower...");
var r1 = db.virtualParameters.updateOne(
  { _id: "getTxPower" },
  { $set: { script: txPowerScript } },
  { upsert: true }
);
print("  matched=" + r1.matchedCount + " upserted=" + r1.upsertedCount);

print("Inserting getWanStatus...");
var r2 = db.virtualParameters.updateOne(
  { _id: "getWanStatus" },
  { $set: { script: wanStatusScript } },
  { upsert: true }
);
print("  matched=" + r2.matchedCount + " upserted=" + r2.upsertedCount);

print("\nVerifying:");
db.virtualParameters.find(
  { _id: { $in: ["getTxPower", "getWanStatus"] } },
  { _id: 1 }
).forEach(function(d) { print("  OK: " + d._id); });
