#!/bin/bash
# Revert cwmp.connectionRequestAuth ke simple AUTH (tidak ada ternary)
# GenieACS expression parser tidak support ?: operator
mongo genieacs --quiet --eval '
var r = db.config.updateOne(
  {_id:"cwmp.connectionRequestAuth"},
  {$set:{value:"AUTH(\"kosong\", \"kosong\")"}}
);
print("reverted: mod=" + r.modifiedCount);
print("value: " + db.config.findOne({_id:"cwmp.connectionRequestAuth"}).value);
'
