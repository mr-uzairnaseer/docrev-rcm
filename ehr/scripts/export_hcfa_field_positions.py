#!/usr/bin/env python3
"""Export CMS-1500 AcroForm field positions for the browser overlay editor."""

from __future__ import annotations

import json
import re
import sys
from pathlib import Path

try:
    import fitz
except ImportError as exc:  # pragma: no cover
    raise SystemExit("PyMuPDF required: pip install pymupdf") from exc

ROOT = Path(__file__).resolve().parent.parent
TEMPLATE = ROOT / "public" / "forms" / "cms1500-blank.pdf"
OUTPUT = ROOT / "public" / "js" / "hcfa-field-positions.json"


def checkbox_on_state(doc: fitz.Document, xref: int) -> str:
    obj = doc.xref_object(xref)
    match = re.search(r"/D\s*<<([^>]+)>>", obj)
    if not match:
        return ""
    keys = re.findall(r"/(\w+)\s+\d+\s+0\s+R", match.group(1))
    for key in keys:
        if key != "Off":
            return key
    return ""


def main() -> int:
    doc = fitz.open(str(TEMPLATE))
    page = doc[0]
    pw, ph = page.rect.width, page.rect.height
    fields: dict = {}

    for widget in page.widgets():
        name = widget.field_name
        if name == "Clear Form":
            continue
        rect = widget.rect
        entry = {
            "x": round(rect.x0 / pw * 100, 3),
            "y": round(rect.y0 / ph * 100, 3),
            "w": round((rect.x1 - rect.x0) / pw * 100, 3),
            "h": round((rect.y1 - rect.y0) / ph * 100, 3),
            "type": widget.field_type_string,
        }
        if widget.field_type_string == "CheckBox":
            entry["on"] = checkbox_on_state(doc, widget.xref)

        if name in fields:
            if isinstance(fields[name], list):
                fields[name].append(entry)
            else:
                fields[name] = [fields[name], entry]
        else:
            fields[name] = entry

    OUTPUT.write_text(
        json.dumps({"pageW": pw, "pageH": ph, "fields": fields}, indent=2),
        encoding="utf-8",
    )
    print(f"Wrote {len(fields)} field names to {OUTPUT}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
