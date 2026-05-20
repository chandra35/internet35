content = open('/opt/genieacs/ext/internet35.js').read()
lines = content.split('\n')
fixed = False
for i, line in enumerate(lines):
    if 'ACS_KEY' in line and '|| " ;' in line:
        lines[i] = line.replace('|| " ;', '|| "";')
        fixed = True
        print('Fixed line', i+1, ':', lines[i])
if fixed:
    open('/opt/genieacs/ext/internet35.js', 'w').write('\n'.join(lines))
    print('File saved')
else:
    print('Pattern not found, line 24:', repr(lines[23]))
