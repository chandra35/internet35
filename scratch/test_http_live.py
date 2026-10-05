import requests
import re

session = requests.Session()
login_page = session.get('http://172.16.2.4/login')

token_match = re.search(r'name="_token"\s+value="([^"]+)"', login_page.text)
if token_match:
    csrf_token = token_match.group(1)
    print("Found CSRF token:", csrf_token[:10] + "...")
    
    login_resp = session.post('http://172.16.2.4/login', data={
        '_token': csrf_token,
        'login': 'superadmin@internet35.com',
        'password': 'password123'
    }, allow_redirects=False)
    print(f"POST /login -> Status: {login_resp.status_code}, Location: {login_resp.headers.get('Location')}")
    
    dash_resp = session.get('http://172.16.2.4/admin/dashboard')
    print(f"GET /admin/dashboard (auth superadmin) -> Status: {dash_resp.status_code}, Length: {len(dash_resp.text)}")
    if dash_resp.status_code != 200:
        print("Response body preview:\n", dash_resp.text[:1000])
    else:
        print("SUCCESS! Admin dashboard rendered 200 OK.")
