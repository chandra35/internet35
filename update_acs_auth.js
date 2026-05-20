// Update GenieACS connection request auth: per-manufacturer
// Huawei -> acs/acs@internet35 ; lainnya (EPON lama) -> kosong/kosong
db.config.updateOne(
  { _id: "cwmp.connectionRequestAuth" },
  { $set: { value: 'DeviceID.Manufacturer = "Huawei Technologies Co., Ltd" ? AUTH("acs", "acs@internet35") : AUTH("kosong", "kosong")' } },
  { upsert: true }
);
print("=== After update ===");
printjson(db.config.findOne({ _id: "cwmp.connectionRequestAuth" }));
