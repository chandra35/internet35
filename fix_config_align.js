// Align cwmp.connectionRequestAuth password with provision (Acs12345)
// And fix PeriodicInformInterval=600
db.config.updateOne(
  {_id:"cwmp.connectionRequestAuth"},
  {$set:{value:'DeviceID.Manufacturer = "Huawei Technologies Co., Ltd" ? AUTH("acs", "Acs12345") : AUTH("kosong", "kosong")'}}
);
print("cwmp.connectionRequestAuth updated:");
printjson(db.config.findOne({_id:"cwmp.connectionRequestAuth"}));
