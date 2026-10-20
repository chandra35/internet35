#!/bin/bash
mongo genieacs --quiet --eval '
var r = db.config.updateOne(
  {_id:"cwmp.connectionRequestAuth"},
  {$set:{value:"AUTH(\"acs\", \"acs@internet35\")"}}
);
print("mod=" + r.modifiedCount);
print("value: " + db.config.findOne({_id:"cwmp.connectionRequestAuth"}).value);
'
