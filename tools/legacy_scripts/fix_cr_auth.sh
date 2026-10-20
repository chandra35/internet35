#!/bin/bash
EVAL='var r=db.config.updateOne({_id:"cwmp.connectionRequestAuth"},{$set:{value:"DeviceID.Manufacturer = \"Huawei Technologies Co., Ltd\" ? AUTH(\"acs\", \"Acs12345\") : AUTH(\"kosong\", \"kosong\")"}});print("mod:"+r.modifiedCount);print(db.config.findOne({_id:"cwmp.connectionRequestAuth"}).value);'
ssh -i /root/.ssh/id_ed25519_genieacs -o StrictHostKeyChecking=no root@172.10.10.254 "mongo genieacs --quiet --eval '$EVAL'"
