import subprocess

js = """
var d = db.devices.findOne({_id: /840472AE/});
var keys = Object.keys(d).filter(function(k) {
  return k.indexOf('WLANConfiguration') > -1 && (
    k.endsWith('.SSID') || k.endsWith('.Enable') || k.endsWith('.BeaconType') || k.endsWith('.SSIDAdvertisementEnabled')
  );
});
keys.sort().forEach(function(k) {
  var val = d[k] ? d[k]['_value'] : null;
  print(k + ' = ' + JSON.stringify(val));
});
"""

r = subprocess.run(['mongo', 'genieacs', '--quiet', '--eval', js], capture_output=True, text=True)
print(r.stdout)
if r.stderr:
    print('ERR:', r.stderr[:300])
