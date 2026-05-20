// TEST MINIMAL: Buat PPPoE jika WANPPPConnectionNumberOfEntries == 0
var numPPPoE = declare(
  "InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnectionNumberOfEntries",
  { value: 1 }
).value[0];

if (numPPPoE === 0 || numPPPoE === "0") {
  declare(
    "InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection",
    null,
    { path: 1 }
  );
}
