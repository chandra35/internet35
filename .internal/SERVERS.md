# Akses Server & Infrastruktur

> **JANGAN PUSH KE GITHUB** — File ini berisi credential.

---

## VM GenieACS (ACS Server)

| Item | Detail |
|------|--------|
| IP | 172.10.10.254 |
| User | root |
| Password | (kosong) |
| OS | (cek via SSH) |
| Fungsi | ACS Server (GenieACS) untuk provisioning ONT via TR-069 |

### Port GenieACS (Default)
| Port | Fungsi |
|------|--------|
| 7547 | CWMP (TR-069) — ONT connect ke sini |
| 3000 | Web UI — admin interface |
| 7557 | NBI (Northbound Interface) — REST API untuk integrasi billing |

### SSH Access
```bash
ssh root@172.10.10.254
# atau dengan key (setelah setup):
ssh -i ~/.ssh/id_ed25519_genieacs root@172.10.10.254
```

---

## Shared Hosting (wifi35.net)

| Item | Detail |
|------|--------|
| Host | wifi35.net |
| User | manmetr1 |
| App Path | /home/manmetr1/internet35-app/ |
| Public Path | /home/manmetr1/wifi35.net/ |
| PHP | 8.3.30 |
| Panel | cPanel |

### Deploy Command
```bash
ssh manmetr1@wifi35.net "cd /home/manmetr1/internet35-app && bash deploy/update.sh"
```

---

## GitHub

| Item | Detail |
|------|--------|
| Repo | https://github.com/chandra35/internet35.git |
| Branch | main |
| User | chandra35 |
| Email | chandra35ok@gmail.com |
