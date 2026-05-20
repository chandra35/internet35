import subprocess

js = r"""
var d = db.devices.findOne({_id: /840472AE/});
var keys = Object.keys(d).filter(function(k) {
  return k.indexOf('WLANConfiguration') > -1;
});
keys.sort().forEach(function(k) {
  if (k.endsWith('.SSID') || k.endsWith('.Enable') || k.endsWith('.BeaconType') || k.endsWith('.SSIDAdvertisementEnabled')) {
    var val = d[k] ? d[k]['_value'] : null;
    print(k + ' = ' + JSON.stringify(val));
  }
});
"""

with open('/tmp/check_wlan.js', 'w') as f:
    f.write(js)

r = subprocess.run(['mongo', 'genieacs', '--quiet', '/tmp/check_wlan.js'], capture_output=True, text=True)
print(r.stdout)
if r.stderr:
    print('ERR:', r.stderr[:300])
