// Temp set ke kosong/kosong agar Huawei ONU yg saat ini punya CR creds kosong bisa di-trigger
db.config.updateOne(
  { _id: "cwmp.connectionRequestAuth" },
  { $set: { value: 'AUTH("kosong", "kosong")' } },
  { upsert: true }
);
printjson(db.config.findOne({ _id: "cwmp.connectionRequestAuth" }));
