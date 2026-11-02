#!/usr/bin/env python3
"""Validate the FDR motor-to-product map shipped with Starparts Motor.

Usage:
  python3 tools/validate_fdr_fitment.py
  python3 tools/validate_fdr_fitment.py --sql-dump /path/to/starparts_db.sql

When a SQL dump is provided, each mapped slug is checked against the products
INSERT rows in that dump. This does not connect to or modify a live database.
"""
from __future__ import annotations

import argparse
import json
import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
MAP_PATH = ROOT / "api" / "whatsapp" / "fdr_fitment.json"
SLUG_RE = re.compile(r"^[a-z0-9]+(?:-[a-z0-9]+)*$")


def sql_product_slugs(path: Path) -> set[str]:
    content = path.read_text(encoding="utf-8", errors="replace")
    marker = "INSERT INTO `products`"
    if marker not in content:
        raise ValueError("Tidak ditemukan INSERT INTO `products` pada SQL dump")
    tail = content.split(marker, 1)[1]
    slugs: set[str] = set()
    started_rows = False
    for line in tail.splitlines():
        line = line.strip()
        if not line:
            continue
        if line.startswith("("):
            started_rows = True
        elif started_rows and (line.startswith("--") or line.startswith("CREATE TABLE") or line.startswith("ALTER TABLE")):
            break
        else:
            continue
        # Read values 1..4 from the row (id, category_id, name, slug).
        i, values = 1, []
        while i < len(line) and len(values) < 4:
            while i < len(line) and line[i] in " \t":
                i += 1
            if i < len(line) and line[i] == "'":
                i += 1
                value = []
                while i < len(line):
                    if line[i] == "'":
                        if i + 1 < len(line) and line[i + 1] == "'":
                            value.append("'")
                            i += 2
                            continue
                        i += 1
                        break
                    if line[i] == "\\" and i + 1 < len(line):
                        value.append(line[i + 1])
                        i += 2
                        continue
                    value.append(line[i])
                    i += 1
                values.append("".join(value))
            else:
                j = i
                while j < len(line) and line[j] not in ",)":
                    j += 1
                values.append(line[i:j].strip())
                i = j
            while i < len(line) and line[i] in " \t":
                i += 1
            if i < len(line) and line[i] == ",":
                i += 1
        if len(values) >= 4 and values[3]:
            slugs.add(values[3])
    if not slugs:
        raise ValueError("Tidak ada slug produk yang bisa dibaca dari SQL dump")
    return slugs


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--sql-dump", type=Path, help="Opsional: SQL dump website untuk validasi slug")
    args = parser.parse_args()

    try:
        data = json.loads(MAP_PATH.read_text(encoding="utf-8"))
    except Exception as exc:
        print(f"ERROR: gagal membaca JSON: {exc}")
        return 1

    errors: list[str] = []
    vehicles = data.get("vehicles")
    if not isinstance(vehicles, list) or not vehicles:
        errors.append("vehicles harus berupa daftar yang tidak kosong")
        vehicles = []

    keys: set[str] = set()
    aliases: dict[str, str] = {}
    referenced_slugs: set[str] = set()
    for vehicle in vehicles:
        key = str(vehicle.get("key", "")).strip()
        if not key:
            errors.append("ada vehicle tanpa key")
            continue
        if key in keys:
            errors.append(f"key duplikat: {key}")
        keys.add(key)
        aliases_list = vehicle.get("aliases", [])
        if not isinstance(aliases_list, list) or not aliases_list:
            errors.append(f"{key}: aliases harus berupa daftar yang tidak kosong")
        for alias in aliases_list:
            alias = str(alias).strip().lower()
            if not alias:
                errors.append(f"{key}: alias kosong")
            elif alias in aliases and aliases[alias] != key:
                # Shared/overlapping aliases often cause model resolution errors.
                if alias not in {"beat", "honda beat", "vario", "honda vario"}:
                    errors.append(f"alias '{alias}' dipakai oleh {aliases[alias]} dan {key}")
            else:
                aliases[alias] = key
        products = vehicle.get("products", [])
        if not isinstance(products, list):
            errors.append(f"{key}: products harus berupa daftar")
            continue
        if "model belum disebutkan" in key and products:
            errors.append(f"{key}: model ambigu harus products=[] supaya bot klarifikasi dulu")
        if "model belum disebutkan" in key and not vehicle.get("clarification"):
            errors.append(f"{key}: klarifikasi belum diisi")
        for slug in products:
            slug = str(slug)
            if not SLUG_RE.fullmatch(slug):
                errors.append(f"{key}: slug tidak valid: {slug}")
            referenced_slugs.add(slug)

    by_key = {v.get("key"): set(v.get("products", [])) for v in vehicles}
    must_have = {
        "honda vario 125": {"city-force", "facio", "flemino", "genzi", "genzi-pro", "hover", "sport-zevo", "sport-xr-evo", "sport-xt", "sport-mp27", "sport-mp76"},
        "honda vario 160": {"hover", "sport-zevo", "sport-mp27", "sport-xr-evo"},
        "honda beat (model belum disebutkan)": set(),
        "honda vario (model belum disebutkan)": set(),
    }
    for key, expected in must_have.items():
        if key not in by_key:
            errors.append(f"mapping penting belum ada: {key}")
        elif by_key[key] != expected and expected:
            errors.append(f"{key}: produk tidak sesuai mapping Phase 3; found={sorted(by_key[key])}")
        elif expected == set() and by_key[key]:
            errors.append(f"{key}: produk harus kosong sampai varian diklarifikasi")

    if args.sql_dump:
        try:
            sql_slugs = sql_product_slugs(args.sql_dump)
            missing = sorted(referenced_slugs - sql_slugs)
            if missing:
                errors.append("slug tidak ditemukan dalam SQL dump: " + ", ".join(missing))
            print(f"SQL slug check: {len(referenced_slugs)} unique mapped slug(s), {len(missing)} missing")
        except Exception as exc:
            errors.append(f"gagal memvalidasi SQL dump: {exc}")

    if errors:
        print("VALIDATION FAILED")
        for err in errors:
            print("- " + err)
        return 1
    print(f"OK: {len(vehicles)} vehicle records, {len(referenced_slugs)} unique product slugs, aliases and ambiguity rules valid.")
    if not args.sql_dump:
        print("INFO: SQL slug existence was not checked because --sql-dump was not provided.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
