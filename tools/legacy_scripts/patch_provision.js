// Patch provision "inform": brand-aware credentials.
// Huawei butuh password >= 8 char + huruf+angka.
// EPON lama tetap pakai "kosong/kosong".

const newInformScript = `// Provision inform (PATCHED 2026-04-23: brand-aware credentials)
const url = "http://172.10.10.254:7547";
const informInterval = 200;

const daily   = Date.now(86400000);
const minutes = Date.now(300000);
const update  = Date.now(60000);
const hourly  = Date.now(3590000);

const brand = declare("DeviceID.Manufacturer", {value: daily}).value[0];
const isHuawei = brand === "Huawei Technologies Co., Ltd";

// Huawei EG8141H5 tolak password < 8 char atau tanpa digit -> CPE fault 9002
const AcsUser     = isHuawei ? "acs"      : "kosong";
const AcsPass     = isHuawei ? "Acs12345" : "kosong";
const ConnReqUser = isHuawei ? "acs"      : "kosong";
const ConnReqPass = isHuawei ? "Acs12345" : "kosong";

if (brand !== "MikroTik") {
    declare("InternetGatewayDevice.ManagementServer.URL",                       {value: daily},  {value: url});
    declare("InternetGatewayDevice.ManagementServer.Username",                  {value: daily},  {value: AcsUser});
    declare("InternetGatewayDevice.ManagementServer.Password",                  {value: daily},  {value: AcsPass});
    declare("InternetGatewayDevice.ManagementServer.ConnectionRequestUsername", {value: update}, {value: ConnReqUser});
    declare("InternetGatewayDevice.ManagementServer.ConnectionRequestPassword", {value: update}, {value: ConnReqPass});
    declare("InternetGatewayDevice.ManagementServer.PeriodicInformEnable",      {value: daily},  {value: true});
    declare("InternetGatewayDevice.ManagementServer.PeriodicInformInterval",    {value: daily},  {value: informInterval});
} else {
    declare("Device.ManagementServer.URL",                       {value: daily},  {value: url});
    declare("Device.ManagementServer.Username",                  {value: daily},  {value: AcsUser});
    declare("Device.ManagementServer.Password",                  {value: daily},  {value: AcsPass});
    declare("Device.ManagementServer.ConnectionRequestUsername", {value: daily},  {value: ConnReqUser});
    declare("Device.ManagementServer.ConnectionRequestPassword", {value: daily},  {value: ConnReqPass});
    declare("Device.ManagementServer.PeriodicInformEnable",      {value: daily},  {value: true});
    declare("Device.ManagementServer.PeriodicInformInterval",    {value: daily},  {value: informInterval});
}`;

const old = db.provisions.findOne({_id: "inform"});
if (old) {
    db.provisions_backup_20260423.insertOne({_id: "inform_bak_" + Date.now(), original: old});
    print("Backup saved.");
}

db.provisions.updateOne({_id: "inform"}, {$set: {script: newInformScript}});
print("=== Updated provision 'inform' ===");
print(db.provisions.findOne({_id: "inform"}).script.substring(0, 400) + "...");
