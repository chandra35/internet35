#!/bin/bash
# EMERGENCY FIX: revert cwmp.connectionRequestAuth - @ tidak valid di expression parser
mongo genieacs --quiet --eval '
// Revert ke Acs12345 dulu (tidak ada @ = aman di expression parser)
var r = db.config.updateOne(
  {_id:"cwmp.connectionRequestAuth"},
  {$set:{value:"DeviceID.Manufacturer = \"Huawei Technologies Co., Ltd\" ? AUTH(\"acs\", \"Acs12345\") : AUTH(\"kosong\", \"kosong\")"}}
);
print("connectionRequestAuth reverted: mod=" + r.modifiedCount);
print("value: " + db.config.findOne({_id:"cwmp.connectionRequestAuth"}).value);
'
