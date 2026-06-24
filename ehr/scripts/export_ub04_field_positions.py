#!/usr/bin/env python3
"""Export UB-04 overlay field positions from official CMS-1450 blank PDF geometry."""

from __future__ import annotations

import json
import sys
from pathlib import Path

try:
    import fitz
except ImportError as exc:  # pragma: no cover
    raise SystemExit("PyMuPDF (pymupdf) is required: pip install pymupdf") from exc

ROOT = Path(__file__).resolve().parent.parent
TEMPLATE = ROOT / "public" / "forms" / "ub04-blank.pdf"
OUTPUT = ROOT / "public" / "js" / "ub04-field-positions.json"


def pct(pw: float, ph: float, x: float, y: float, w: float, h: float) -> dict:
    return {
        "x": round(x / pw * 100, 3),
        "y": round(y / ph * 100, 3),
        "w": round(w / pw * 100, 3),
        "h": round(h / ph * 100, 3),
        "type": "Text",
    }


def detect_lines(page: fitz.Page, pw: float, ph: float) -> tuple[list[float], list[float]]:
    horiz: set[float] = set()
    verts: set[float] = set()
    for drawing in page.get_drawings():
        for item in drawing["items"]:
            if item[0] != "l":
                continue
            p1, p2 = item[1], item[2]
            if abs(p1.y - p2.y) < 0.5 and abs(p1.x - p2.x) > 180:
                horiz.add(round(p1.y / ph * 100, 2))
            if abs(p1.x - p2.x) < 0.5 and abs(p1.y - p2.y) > 8:
                verts.add(round(p1.x / pw * 100, 2))
    return sorted(horiz), sorted(verts)


def dedupe_rows(rows: list[float], min_gap: float = 0.8) -> list[float]:
    tops: list[float] = []
    for y in rows:
        if not tops or y - tops[-1] > min_gap:
            tops.append(y)
    return tops


def box_from_grid(
    pw: float,
    ph: float,
    verts: list[float],
    row_tops: list[float],
    row_idx: int,
    col_left: float,
    col_right: float,
    pad_x: float = 0.15,
    pad_y: float = 0.2,
) -> dict:
    y_top = row_tops[row_idx] + pad_y
    y_bot = row_tops[row_idx + 1] - pad_y if row_idx + 1 < len(row_tops) else row_tops[row_idx] + 1.4
    x = col_left + pad_x
    w = max(1.0, col_right - col_left - pad_x * 2)
    h = max(0.8, y_bot - y_top)
    return pct(pw, ph, x / 100 * pw, y_top / 100 * ph, w / 100 * pw, h / 100 * ph)


def main() -> int:
    doc = fitz.open(str(TEMPLATE))
    page = doc[0]
    pw, ph = page.rect.width, page.rect.height
    horiz, verts = detect_lines(page, pw, ph)
    fields: list[dict] = []

    def add(key: str, x: float, y: float, w: float, h: float) -> None:
        fields.append({"key": key, **pct(pw, ph, x, y, w, h)})

    # Revenue grid columns from detected verticals (percent).
    rev_cols = {
        "fl42": (verts[1] if len(verts) > 1 else 6.5, verts[2] if len(verts) > 2 else 36.0),
        "fl43": (verts[2] if len(verts) > 2 else 36.0, verts[3] if len(verts) > 3 else 54.0),
        "fl44": (verts[3] if len(verts) > 3 else 54.0, verts[4] if len(verts) > 4 else 62.5),
        "fl45": (verts[4] if len(verts) > 4 else 62.5, verts[5] if len(verts) > 5 else 69.0),
        "fl46": (verts[5] if len(verts) > 5 else 69.0, verts[6] if len(verts) > 6 else 72.0),
        "fl47": (verts[6] if len(verts) > 6 else 72.0, verts[7] if len(verts) > 7 else 81.0),
        "fl48": (verts[7] if len(verts) > 7 else 81.0, verts[8] if len(verts) > 8 else 84.0),
    }
    rev_rows = dedupe_rows([y for y in horiz if 25 < y < 61.5])

    # Header / patient blocks (calibrated to PAN template).
    add("fl1.name", 10, 16, 175, 11)
    add("fl1.address", 10, 28, 175, 11)
    add("fl1.city", 10, 40, 175, 11)
    add("fl1.phone", 10, 52, 90, 11)
    add("fl2.payto", 188, 16, 115, 48)
    add("fl3a", 310, 16, 75, 11)
    add("fl3b", 310, 28, 75, 11)
    add("fl4", 552, 8, 42, 14)
    add("fl5", 368, 38, 72, 11)
    add("fl6.from", 440, 34, 42, 11)
    add("fl6.through", 488, 34, 42, 11)
    add("fl7", 538, 30, 55, 11)

    add("fl8", 10, 58, 210, 11)
    add("fl9.street", 225, 58, 210, 11)
    add("fl9.city", 225, 70, 210, 11)
    add("fl10.dob", 10, 84, 50, 11)
    add("fl10.sex", 68, 84, 22, 11)
    add("fl12", 128, 84, 50, 11)
    add("fl13", 185, 84, 28, 11)
    add("fl14", 220, 84, 28, 11)
    add("fl15", 255, 84, 22, 11)
    add("fl16", 285, 82, 28, 11)
    add("fl17", 320, 82, 28, 11)

    cond_x = [248, 272, 296, 320, 344, 368, 392, 416, 440, 464, 488]
    for i, x in enumerate(cond_x):
        add(f"fl{18 + i}", x, 84, 22, 11)

    add("fl29", 478, 82, 28, 11)
    add("fl30", 512, 82, 28, 11)

    occ_cols = [(8, 52), (82, 126), (155, 199), (228, 272)]
    for row, y in enumerate([108, 122]):
        for col, (x_code, x_date) in enumerate(occ_cols):
            idx = row * 4 + col
            if idx < 8:
                add(f"fl31_34.{idx}.code", x_code, y, 38, 10)
                add(f"fl31_34.{idx}.date", x_date, y, 38, 10)

    for col, (x_code, x_date) in enumerate([(300, 374), (420, 494)]):
        add(f"fl35_36.{col}.code", x_code, 108, 38, 10)
        add(f"fl35_36.{col}.from", x_date, 108, 38, 10)
        add(f"fl35_36.{col}.through", x_date, 122, 38, 10)

    add("fl37", 8, 148, 300, 11)

    for row, y in enumerate([148, 162]):
        for col, (x_code, x_amt) in enumerate([(310, 358), (368, 416), (426, 474), (484, 532)]):
            idx = row * 4 + col
            if idx < 12:
                add(f"fl39_41.{idx}.code", x_code, y, 38, 10)
                add(f"fl39_41.{idx}.amount", x_amt, y, 38, 10)

    for i in range(min(22, max(0, len(rev_rows) - 1))):
        for field, (left, right) in rev_cols.items():
            fields.append(
                {"key": f"line{i}.{field}", **box_from_grid(pw, ph, verts, rev_rows, i, left, right)}
            )

    if rev_rows:
        total_y = rev_rows[min(len(rev_rows) - 1, 22)] / 100 * ph + 2
        add("fl47total", rev_cols["fl47"][0] / 100 * pw, total_y, (rev_cols["fl47"][1] - rev_cols["fl47"][0]) / 100 * pw, 12)
        add("fl48total", rev_cols["fl48"][0] / 100 * pw, total_y, (rev_cols["fl48"][1] - rev_cols["fl48"][0]) / 100 * pw, 12)
        add("fl49total", 575, total_y, 22, 12)

    payer_y = [486, 510, 534]
    for i, y in enumerate(payer_y):
        add(f"fl50.{i}.name", 8, y, 115, 10)
        add(f"fl50.{i}.health_plan_id", 128, y, 70, 10)
        add(f"fl50.{i}.release_info", 205, y, 18, 10)
        add(f"fl50.{i}.assign_benefits", 228, y, 18, 10)
        add(f"fl50.{i}.prior_payments", 305, y, 55, 10)
        add(f"fl50.{i}.estimated_amount", 395, y, 55, 10)

    add("fl56", 468, 498, 75, 11)
    add("fl57", 545, 498, 50, 11)

    for i, y in enumerate([538, 552, 566]):
        add(f"fl58.{i}.name", 8, y, 115, 10)
        add(f"fl59.{i}.rel", 128, y, 28, 10)
        add(f"fl60.{i}.id", 165, y, 70, 10)
        add(f"fl61.{i}.group_name", 245, y, 70, 10)
        add(f"fl62.{i}.group_no", 325, y, 55, 10)

    add("fl63", 8, 588, 115, 11)
    add("fl64", 245, 588, 90, 11)
    add("fl65", 420, 588, 90, 11)
    add("fl66", 8, 628, 18, 11)

    dx_x = [30, 72, 114, 156, 198, 240, 282, 324, 366]
    for row, y in enumerate([642, 656]):
        for col, x in enumerate(dx_x):
            idx = row * 9 + col
            if idx < 18:
                add(f"fl67.{idx}", x, y, 38, 10)

    add("fl69", 10, 655, 55, 11)
    add("fl70", 88, 655, 55, 11)
    add("fl71", 165, 655, 55, 11)
    add("fl72", 275, 655, 55, 11)

    add("fl74.code", 10, 668, 55, 10)
    add("fl74.date", 72, 668, 42, 10)
    for i, x in enumerate([165, 248]):
        add(f"fl75.{i}.code", x, 668, 55, 10)
        add(f"fl75.{i}.date", x + 58, 668, 42, 10)
    add("fl76.qualifier", 370, 668, 18, 10)
    add("fl76.npi", 395, 668, 55, 10)
    add("fl76.name", 455, 668, 90, 10)

    for i, y in enumerate([688, 702, 716]):
        add(f"fl77_79.{i}.qualifier", 10, y, 18, 10)
        add(f"fl77_79.{i}.npi", 35, y, 55, 10)
        add(f"fl77_79.{i}.name", 100, y, 90, 10)

    add("fl80", 8, 718, 360, 28)

    OUTPUT.write_text(
        json.dumps({"pageW": pw, "pageH": ph, "fields": fields}, indent=2),
        encoding="utf-8",
    )
    print(f"Wrote {len(fields)} fields to {OUTPUT}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
