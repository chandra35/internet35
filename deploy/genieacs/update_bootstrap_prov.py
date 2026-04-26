import subprocess, json, sys

with open('/tmp/bootstrap_new.js') as f:
    script = f.read()

# Build the mongo --eval string carefully
eval_js = 'db.provisions.replaceOne({_id:"bootstrap"},{_id:"bootstrap",script:' + json.dumps(script) + '},{upsert:true}); print("bootstrap provision updated OK");'

result = subprocess.run(
    ['mongo', 'genieacs', '--quiet', '--eval', eval_js],
    capture_output=True, text=True
)
print(result.stdout)
if result.stderr:
    print("STDERR:", result.stderr, file=sys.stderr)
sys.exit(result.returncode)
