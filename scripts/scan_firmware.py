#!/usr/bin/env python3
"""
scan_firmware.py — Extract version/brand/model metadata from firmware binary.

Usage:
    python3 scan_firmware.py /path/to/firmware.bin
    python3 scan_firmware.py /path/to/firmware.bin --max-mb 4

Output: JSON to stdout
    {
        "brand":   "huawei",
        "model":   "HG8145V5",
        "version": "V5R021C10S030",
        "extra":   ["V300R019C00SPC300", ...],
        "source":  "binary_scan"
    }
    On error:
    {"error": "reason"}
"""

import sys
import re
import json
import os
import argparse


# ---------------------------------------------------------------------------
# Config
# ---------------------------------------------------------------------------
DEFAULT_MAX_BYTES = 6 * 1024 * 1024   # scan first 6 MB
MIN_STRING_LEN    = 5

# ---------------------------------------------------------------------------
# Brand fingerprints (checked against extracted strings)
# Order matters — more specific first
# ---------------------------------------------------------------------------
BRAND_PATTERNS = [
    # (compiled regex on string, brand, model regex or None)
    (re.compile(r'\b(HG8\d{3}[A-Z0-9]+|MA5\d{3}[A-Z0-9]+|EG8\d{3}[A-Z0-9]+|HN8\d{3}[A-Z0-9]+|WA8\d{3}[A-Z0-9]+)\b', re.I),
     'huawei',
     re.compile(r'\b(HG8\d{3}[A-Z0-9]+|MA5\d{3}[A-Z0-9]+|EG8\d{3}[A-Z0-9]+|HN8\d{3}[A-Z0-9]+|WA8\d{3}[A-Z0-9]+)\b', re.I)),

    (re.compile(r'\b(ZXHN[-_ ]?[A-Z0-9]+|ZTEG\b)', re.I),
     'zte',
     re.compile(r'\b(ZXHN[-_ ]?[A-Z0-9]+)\b', re.I)),

    (re.compile(r'\b(AN\d{4}[-A-Z0-9]+|HG6\d{3}[A-Z0-9]*|AN5\d{3}[A-Z0-9]+|FiberHome)\b', re.I),
     'fiberhome',
     re.compile(r'\b(AN\d{4}[-A-Z0-9]+|HG6\d{3}[A-Z0-9]*|AN5\d{3}[A-Z0-9]+)\b', re.I)),

    (re.compile(r'\b(G-\d{4}[A-Z0-9-]*|BONT\d+|Nokia Optical)\b', re.I),
     'nokia',
     re.compile(r'\b(G-\d{4}[A-Z0-9-]*|BONT\d+)\b', re.I)),

    (re.compile(r'\b(Archer[-_ ]?[A-Z0-9]+|TP-LINK|TP_LINK)\b', re.I),
     'tp-link',
     re.compile(r'\b(Archer[-_ ]?[A-Z0-9]+|TL-[A-Z0-9]+)\b', re.I)),

    (re.compile(r'\bHuawei\b', re.I), 'huawei', None),
    (re.compile(r'\bZTE\b'),           'zte',    None),
    (re.compile(r'\bFiberHome\b', re.I), 'fiberhome', None),
]

# Version patterns ordered by specificity (most specific first)
VERSION_PATTERNS = [
    re.compile(r'\b(V\d+R\d{3}C\d{2}S\d{3})\b', re.I),   # Huawei: V5R021C10S030
    re.compile(r'\b(V\d+R\d{3}C\d{2}SPC\d+)\b', re.I),   # Huawei enterprise: V300R019C00SPC300
    re.compile(r'\b(V\d+R\d{2,3}C\d{2})\b', re.I),        # Huawei: V5R021C10
    re.compile(r'\b(RP\d{4,6})\b'),                        # FiberHome: RP2723
    re.compile(r'\b(V\d+\.\d+\.\d+[A-Z0-9._-]{0,10})\b', re.I),  # ZTE/Nokia: V1.0.10P1T5
    re.compile(r'\b(\d+\.\d+\.\d+\.\d+)\b'),               # quad: 3.5.1.0
]

# Strings that look like versions but are noise — skip these
VERSION_NOISE = re.compile(
    r'^(1\.0\.0\.0|0\.0\.0\.0|127\.0\.0\.1|255\.255\.\d|192\.168\.|'
    r'10\.\d+\.\d+|172\.\d+\.\d+|2001:|fe80:)',
    re.I
)


# ---------------------------------------------------------------------------
# Core
# ---------------------------------------------------------------------------

def extract_strings(data: bytes, min_len: int = MIN_STRING_LEN):
    """Extract printable ASCII sequences from binary data."""
    return re.findall(rb'[\x20-\x7e]{' + str(min_len).encode() + rb',}', data)


def scan(filepath: str, max_bytes: int = DEFAULT_MAX_BYTES) -> dict:
    if not os.path.isfile(filepath):
        return {"error": f"File not found: {filepath}"}

    file_size = os.path.getsize(filepath)

    with open(filepath, 'rb') as f:
        # Read beginning AND end (version strings sometimes near EOF too)
        head = f.read(max_bytes)
        tail = b''
        if file_size > max_bytes + 65536:
            f.seek(max(0, file_size - 65536))
            tail = f.read(65536)

    chunks = [head, tail]
    all_strings = []
    for chunk in chunks:
        all_strings += [s.decode('ascii', errors='ignore') for s in extract_strings(chunk)]

    # Deduplicate while preserving order
    seen = set()
    strings = []
    for s in all_strings:
        if s not in seen:
            seen.add(s)
            strings.append(s)

    result = {
        "brand":   None,
        "model":   None,
        "version": None,
        "extra":   [],
        "source":  "binary_scan",
        "strings_scanned": len(strings),
    }

    # --- Detect brand + model ---
    for s in strings:
        if result['brand']:
            break
        for (brand_re, brand, model_re) in BRAND_PATTERNS:
            if brand_re.search(s):
                result['brand'] = brand
                if model_re:
                    m = model_re.search(s)
                    if m:
                        result['model'] = m.group(1).upper().replace(' ', '')
                break

    # --- Detect version ---
    version_candidates = []
    for s in strings:
        for vp in VERSION_PATTERNS:
            m = vp.search(s)
            if m:
                v = m.group(1).upper()
                if not VERSION_NOISE.match(v) and v not in version_candidates:
                    version_candidates.append(v)

    if version_candidates:
        result['version'] = version_candidates[0]
        # Add extra unique candidates (skip duplicates and very similar ones)
        for c in version_candidates[1:6]:
            if c != result['version']:
                result['extra'].append(c)

    return result


# ---------------------------------------------------------------------------
# Entry point
# ---------------------------------------------------------------------------

if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('filepath', help='Path to firmware binary file')
    parser.add_argument('--max-mb', type=int, default=6, help='Max MB to scan (default: 6)')
    args = parser.parse_args()

    try:
        out = scan(args.filepath, args.max_mb * 1024 * 1024)
    except Exception as e:
        out = {"error": str(e)}

    print(json.dumps(out))
