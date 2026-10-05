import requests
import re

accounts = [
    ('superadmin@internet35.com', 'password123'),
    ('admin@internet35.com', 'password123'),
]

for email, pwd in accounts:
    session = requests.Session()
    login_page = session.get('http://172.16.2.4/login')

    token_match = re.search(r'name="_token"\s+value="([^"]+)"', login_page.text)
    if token_match:
        csrf_token = token_match.group(1)
        login_resp = session.post('http://172.16.2.4/login', data={
            '_token': csrf_token,
            'login': email,
            'password': pwd
        }, allow_redirects=False)
        print(f"User: {email} | POST /login -> Status: {login_resp.status_code}, Location: {login_resp.headers.get('Location')}")
        
        dash_resp = session.get('http://172.16.2.4/admin/dashboard')
        print(f"User: {email} | GET /admin/dashboard -> Status: {dash_resp.status_code}, Length: {len(dash_resp.text)}")
