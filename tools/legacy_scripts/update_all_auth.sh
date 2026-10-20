#!/bin/bash
set -e

# ====================================================================
# Update semua credential ACS ke: username=acs, password=acs@internet35
# ====================================================================
NEW_PASS="acs@internet35"
NEW_USER="acs"

ssh -i /root/.ssh/id_ed25519_genieacs -o StrictHostKeyChecking=no root@172.10.10.254 bash << 'REMOTE'
NEW_USER="acs"
NEW_PASS="acs@internet35"

mongo genieacs --quiet --eval "
// 1. Update cwmp.connectionRequestAuth
var r1 = db.config.updateOne(
  {_id:'cwmp.connectionRequestAuth'},
  {\$set:{value:'DeviceID.Manufacturer = \"Huawei Technologies Co., Ltd\" ? AUTH(\"'+NEW_USER+'\", \"'+NEW_PASS+'\") : AUTH(\"kosong\", \"kosong\")'}}
);
print('connectionRequestAuth mod:'+r1.modifiedCount);
print('  value: '+db.config.findOne({_id:'cwmp.connectionRequestAuth'}).value);
" 2>/dev/null

# 2. Update provision inform
mongo genieacs --quiet << 'MONGO'
var NEW_USER = "acs";
var NEW_PASS = "acs@internet35";

var provision = db.provisions.findOne({_id: "inform"});
if (!provision) { print("ERROR: provision inform not found"); quit(1); }

print("=== Current provision inform (first 300 chars) ===");
print(provision.script.substring(0, 300));

// Replace password values in the script
var script = provision.script;
var orig = script;

// Replace Acs12345 with acs@internet35
script = script.split("Acs12345").join(NEW_PASS);

if (script === orig) {
  print("WARNING: Acs12345 not found in script - checking current content");
  // Try to find current password pattern
  var match = script.match(/const AcsPass\s*=\s*"([^"]*)"/);
  if (match) print("  Current AcsPass: " + match[1]);
  var match2 = script.match(/isHuawei \? "acs" : "kosong";/);
  print("  isHuawei pattern found: " + !!match2);
}

// Save backup
var backup = db.provisions.findOne({_id: "inform_backup_acs_pass"});
if (!backup) {
  db.provisions.insertOne({_id: "inform_backup_acs_pass", script: orig, _savedAt: new Date()});
  print("Backup saved as inform_backup_acs_pass");
}

// Update
var r = db.provisions.updateOne({_id:"inform"}, {\$set:{script: script}});
print("provision inform mod: " + r.modifiedCount);
MONGO

REMOTE
