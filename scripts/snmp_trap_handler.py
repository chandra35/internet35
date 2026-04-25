#!/usr/bin/env python3
"""
ZTE C320 SNMP Trap Handler for Internet35
=========================================
Receives ONU online/offline traps from OLT ZTE C320 and updates DB.

snmptrapd calls this script via traphandle with data on stdin:
  Line 1: hostname
  Line 2: IP address
  Line 3+: OID value pairs (space separated)

ZTE C320 ONU state trap OIDs:
  - zxAnGponOnuStateTrap: 1.3.6.1.4.1.3902.1082.500.10.1.3.1
  - zxAnGponOnuDeregTrap: 1.3.6.1.4.1.3902.1082.500.10.1.3.2
  
ONU Index encoding (ZTE C320):
  index = (shelf << 24) | (slot << 16) | (port << 8) | onu_id
  Default: shelf=1, slot from card, port 1-8/16, onu_id 1-128

Run status values: 1=online, 2=offline, 3=los, 4=dying_gasp, 5=power_off
"""

import sys
import os
import re
import logging
import datetime
import mysql.connector
from mysql.connector import Error

# ── Config ────────────────────────────────────────────────────────────────
DB_HOST     = '127.0.0.1'
DB_PORT     = 3306
DB_NAME     = 'internet35'
DB_USER     = 'internet35'
DB_PASS     = 'billing35db'
LOG_FILE    = '/var/log/zte_snmp_trap.log'

# ONU run status map (integer → string)
RUN_STATUS_MAP = {
    '1': 'online',
    '2': 'offline',
    '3': 'los',
    '4': 'dying_gasp',
    '5': 'power_off',
}

# OIDs of interest
OID_ONU_RUN_STATUS  = '1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.11'
OID_ONU_SERIAL      = '1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.6'
OID_ONU_AUTH_INFO   = '1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.18'
OID_ONU_NAME        = '1.3.6.1.4.1.3902.1082.500.10.2.3.3.1.2'

# Trap notification OIDs that we care about
TRAP_ONU_STATE = '1.3.6.1.4.1.3902.1082.500.10.1.3.1'   # ONU state change
TRAP_ONU_DEREG = '1.3.6.1.4.1.3902.1082.500.10.1.3.2'   # ONU deregister
TRAP_ONU_REG   = '1.3.6.1.4.1.3902.1082.500.10.1.3.3'   # ONU register

# ── Logging ───────────────────────────────────────────────────────────────
logging.basicConfig(
    filename=LOG_FILE,
    level=logging.DEBUG,
    format='%(asctime)s [%(levelname)s] %(message)s',
)
log = logging.getLogger(__name__)

# ── Helpers ───────────────────────────────────────────────────────────────

def parse_onu_index(index_str):
    """
    ZTE ONU index can arrive as:
      - Dotted notation: 'shelf.slot.port.onu_id'  (e.g. '0.1.1.1')
      - Single integer:  (shelf<<24)|(slot<<16)|(port<<8)|onu_id
    """
    index_str = str(index_str).strip()
    # Dotted: strip leading dot then split
    parts = index_str.lstrip('.').split('.')
    if len(parts) == 4:
        try:
            shelf, slot, port, onu_id = (int(p) for p in parts)
            return shelf, slot, port, onu_id
        except (ValueError, TypeError):
            pass
    # Single integer
    try:
        idx = int(index_str)
        onu_id = idx & 0xFF
        port   = (idx >> 8) & 0xFF
        slot   = (idx >> 16) & 0xFF
        shelf  = (idx >> 24) & 0xFF
        return shelf, slot, port, onu_id
    except (ValueError, TypeError):
        pass
    return None, None, None, None


def parse_serial_hex(hex_str):
    """
    Convert ZTE hex serial (e.g. '48575443xxxxxxxx') to human readable (e.g. 'HWTCxxxxxxxx').
    """
    s = re.sub(r'\s+', '', str(hex_str))
    s = re.sub(r'^0x', '', s, flags=re.IGNORECASE)
    # Remove 'Hex:' prefix if present
    s = re.sub(r'^[Hh]ex:', '', s)
    try:
        # Convert pairs of hex to ASCII for first 4 chars (vendor prefix)
        vendor_hex = s[:8]
        serial_hex = s[8:]
        vendor = bytes.fromhex(vendor_hex).decode('ascii', errors='replace')
        return vendor + serial_hex.upper()
    except Exception:
        return s.upper()


def db_connect():
    return mysql.connector.connect(
        host=DB_HOST, port=DB_PORT, database=DB_NAME,
        user=DB_USER, password=DB_PASS, connection_timeout=5
    )


def update_onu_status(olt_ip, slot, port, onu_id, status, serial=None):
    """Update ONU status in DB based on OLT IP + position."""
    conn = None
    try:
        conn = db_connect()
        cur = conn.cursor(dictionary=True)

        # Find OLT by IP
        cur.execute("SELECT id FROM olts WHERE ip_address = %s LIMIT 1", (olt_ip,))
        olt = cur.fetchone()
        if not olt:
            log.warning(f"OLT {olt_ip} not found in DB")
            return False
        olt_id = olt['id']

        # Try find ONU by serial first (most reliable)
        onu = None
        if serial and len(serial) > 4:
            cur.execute(
                "SELECT id, status FROM onus WHERE olt_id=%s AND serial_number=%s AND deleted_at IS NULL LIMIT 1",
                (olt_id, serial)
            )
            onu = cur.fetchone()

        # Fallback: find by position
        if not onu:
            cur.execute(
                "SELECT id, status FROM onus WHERE olt_id=%s AND slot=%s AND port=%s AND onu_id=%s AND deleted_at IS NULL LIMIT 1",
                (olt_id, slot, port, onu_id)
            )
            onu = cur.fetchone()

        if not onu:
            log.warning(f"ONU not found: OLT={olt_ip} slot={slot} port={port} onu_id={onu_id} serial={serial}")
            return False

        old_status = onu['status']
        if old_status == status:
            log.debug(f"ONU {onu['id']} status unchanged: {status}")
            return True

        # Update status
        now = datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        if status == 'online':
            cur.execute(
                "UPDATE onus SET status=%s, last_online_at=%s, updated_at=%s WHERE id=%s",
                (status, now, now, onu['id'])
            )
        else:
            cur.execute(
                "UPDATE onus SET status=%s, updated_at=%s WHERE id=%s",
                (status, now, onu['id'])
            )
        conn.commit()
        log.info(f"✓ ONU {onu['id']} status: {old_status} → {status} (OLT={olt_ip} {slot}/{port}/{onu_id})")
        return True

    except Error as e:
        log.error(f"DB error: {e}")
        return False
    finally:
        if conn and conn.is_connected():
            conn.close()


# ── Main ──────────────────────────────────────────────────────────────────

def main():
    try:
        lines = sys.stdin.read().splitlines()
    except Exception as e:
        log.error(f"Failed to read stdin: {e}")
        return

    if len(lines) < 2:
        log.warning(f"Too few lines from snmptrapd: {lines}")
        return

    hostname = lines[0].strip()
    raw_addr = lines[1].strip()

    # snmptrapd v5.8 can format line 2 as:
    #   "UDP: [1.2.3.4]:port->[5.6.7.8]:162"  (source IP is first IP)
    #   or plain "1.2.3.4"
    m = re.search(r'\[(\d+\.\d+\.\d+\.\d+)\]', raw_addr)
    olt_ip = m.group(1) if m else raw_addr.split(':')[0].strip()

    def normalize_oid(oid):
        """Replace iso. prefix with 1. and remove MIB name prefixes."""
        oid = re.sub(r'^iso\.', '1.', oid)
        # Strip MIB name prefixes like 'SNMPv2-MIB::sysUpTime.0' -> numeric
        if '::' in oid:
            oid = oid.split('::')[1]  # keep suffix only (may still be named)
        return oid

    # Parse varbinds: "OID VALUE"
    varbinds = {}
    trap_oid = None
    for line in lines[2:]:
        line = line.strip()
        if not line:
            continue
        parts = line.split(None, 1)
        if len(parts) == 2:
            oid_raw, val = parts[0].strip(), parts[1].strip()
            oid = normalize_oid(oid_raw)
            # Normalize value OID too (for snmpTrapOID value)
            val_norm = normalize_oid(val)
            varbinds[oid] = val
            # SNMPv2-MIB::snmpTrapOID.0
            if 'snmpTrapOID' in oid or oid == '1.3.6.1.6.3.1.1.4.1.0':
                trap_oid = val_norm.strip()

    log.debug(f"Trap from {olt_ip} ({hostname}): trapOID={trap_oid}, varbinds_count={len(varbinds)}")
    log.debug(f"Raw varbinds: {varbinds}")

    # Extract ONU info from varbinds
    status = None
    slot, port, onu_id = None, None, None
    serial = None

    for oid, val in varbinds.items():
        # Run status OID: ...zxAnGponOnuRunStatus.index
        if oid.startswith(OID_ONU_RUN_STATUS + '.'):
            index_str = oid[len(OID_ONU_RUN_STATUS)+1:]
            _, slot, port, onu_id = parse_onu_index(index_str)
            status = RUN_STATUS_MAP.get(val.strip(), None)
            log.debug(f"RunStatus OID: index={index_str} slot={slot} port={port} onu={onu_id} status={status}")

        # Serial number OID
        elif oid.startswith(OID_ONU_SERIAL + '.') or oid.startswith(OID_ONU_AUTH_INFO + '.'):
            serial_raw = val.strip()
            if not serial:
                serial = parse_serial_hex(serial_raw) if re.match(r'^[0-9a-fA-F\s]+$', serial_raw.replace('Hex:','').replace('0x','').strip()) else serial_raw
                log.debug(f"Serial from varbind: raw={serial_raw} parsed={serial}")

    # Also check for integer status values in ANY varbind (fallback)
    if status is None:
        for oid, val in varbinds.items():
            if val.strip() in RUN_STATUS_MAP:
                status = RUN_STATUS_MAP[val.strip()]
                # Try to get index from OID
                oid_parts = oid.rsplit('.', 4)
                if len(oid_parts) >= 2:
                    try:
                        idx = int(oid_parts[-1])
                        _, slot, port, onu_id = parse_onu_index(str(idx))
                    except Exception:
                        pass
                break

    # Determine status from trap OID if no RunStatus varbind
    if status is None and trap_oid:
        if TRAP_ONU_DEREG in str(trap_oid) or 'deregis' in str(trap_oid).lower():
            status = 'offline'
        elif TRAP_ONU_REG in str(trap_oid) or 'regis' in str(trap_oid).lower():
            status = 'online'

    if status is None:
        log.debug(f"Could not determine status from trap (may be unrelated trap). OIDs: {list(varbinds.keys())}")
        return

    if slot is None or port is None or onu_id is None:
        log.warning(f"Could not parse ONU position from trap: slot={slot} port={port} onu_id={onu_id}")
        # Try to still update by serial if we have it
        if not serial:
            return

    log.info(f"Processing: OLT={olt_ip} slot={slot} port={port} onu_id={onu_id} serial={serial} → {status}")
    update_onu_status(olt_ip, slot, port, onu_id, status, serial)


if __name__ == '__main__':
    main()
