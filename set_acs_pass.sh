#!/bin/bash
# Update semua: provision inform + cwmp.connectionRequestAuth
mongo genieacs --quiet --eval '
var s = db.provisions.findOne({_id:"inform"}).script;

// Backup jika belum ada
if (!db.provisions.findOne({_id:"inform_backup_20260423_acs"})) {
  db.provisions.insertOne({_id:"inform_backup_20260423_acs", script: s, savedAt: new Date()});
  print("Backup: inform_backup_20260423_acs");
}

// Ganti Acs12345 -> acs@internet35
var s2 = s.split("Acs12345").join("acs@internet35");
var count = (s.match(/Acs12345/g)||[]).length;
print("Occurrences replaced: " + count);

db.provisions.updateOne({_id:"inform"}, {$set:{script: s2}});
print("provision inform updated");

// Cek hasil
var check = db.provisions.findOne({_id:"inform"}).script;
var stillOld = check.indexOf("Acs12345");
print("Acs12345 still present: " + (stillOld >= 0));
var hasNew = check.indexOf("acs@internet35");
print("acs@internet35 present: " + (hasNew >= 0));

// Update cwmp.connectionRequestAuth
var r = db.config.updateOne(
  {_id:"cwmp.connectionRequestAuth"},
  {$set:{value:"DeviceID.Manufacturer = \"Huawei Technologies Co., Ltd\" ? AUTH(\"acs\", \"acs@internet35\") : AUTH(\"kosong\", \"kosong\")"}}
);
print("connectionRequestAuth mod: " + r.modifiedCount);
print("  value: " + db.config.findOne({_id:"cwmp.connectionRequestAuth"}).value);
'
