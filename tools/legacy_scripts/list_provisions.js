db.provisions.find().forEach(function(d) {
  print("=== _id: " + d._id + " ===");
  print(d.script);
  print("");
});
print("--- presets ---");
db.presets.find().forEach(function(d) {
  print("preset: " + d._id);
  printjson(d);
});
