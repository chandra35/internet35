import subprocess

js = r"""
var d = db.devices.findOne({_id: '00259E-HG8145X6%2D10-48575443840472AE'});
if (!d) { print('device not found'); quit(); }
var keys = [];
function walk(obj, prefix) {
  for (var k in obj) {
    var full = prefix ? prefix + '.' + k : k;
    if (full.indexOf('WLANConfiguration') > -1 && (
      full.endsWith('.SSID') || full.endsWith('.Enable') ||
      full.endsWith('.BeaconType') || full.endsWith('.SSIDAdvertisementEnabled')
    )) {
      keys.push(full);
    } else if (typeof obj[k] === 'object' && obj[k] !== null && !obj[k].hasOwnProperty('_value')) {
      walk(obj[k], full);
    }
  }
}
walk(d, '');
keys.sort().forEach(function(k) {
  var parts = k.split('.');
  var cur = d;
  for (var i = 0; i < parts.length; i++) { cur = cur[parts[i]]; if (!cur) break; }
  print(k + ' = ' + JSON.stringify(cur ? cur._value : null));
});
"""

with open('/tmp/cw3.js', 'w') as f:
    f.write(js)

r = subprocess.run(['mongo', 'genieacs', '--quiet', '/tmp/cw3.js'], capture_output=True, text=True)
print(r.stdout if r.stdout else '(no output)')
if r.stderr:
    print('ERR:', r.stderr[:500])
