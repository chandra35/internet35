import requests
import re

# 1. Unauthenticated request
r_unauth = requests.get('http://172.16.2.4/admin/dashboard', allow_redirects=False)
print(f"1. Unauthenticated /admin/dashboard -> Status: {r_unauth.status_code}, Redirect: {r_unauth.headers.get('Location')}")

# 2. Fresh session for login
s = requests.Session()
l_page = s.get('http://172.16.2.4/login')
match = re.search(r'value="([^"]+)"', l_page.text)
token = match.group(1) if match else None
print(f"CSRF token found: {token[:10] if token else 'None'}")

post_login = s.post('http://172.16.2.4/login', data={
    '_token': token,
    'login': 'superadmin@internet35.com',
    'password': 'password123'
}, allow_redirects=False)
print(f"2. POST /login -> Status: {post_login.status_code}, Redirect: {post_login.headers.get('Location')}")

# 3. Access admin dashboard
r_dash = s.get('http://172.16.2.4/admin/dashboard')
print(f"3. Authenticated /admin/dashboard -> Status: {r_dash.status_code}, Response HTML length: {len(r_dash.text)}")

# 4. Access admin customers
r_cust = s.get('http://172.16.2.4/admin/customers')
print(f"4. Authenticated /admin/customers -> Status: {r_cust.status_code}, Response HTML length: {len(r_cust.text)}")
