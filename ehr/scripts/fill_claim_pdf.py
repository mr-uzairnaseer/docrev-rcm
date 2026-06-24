#!/usr/bin/env python3
"""Fill official CMS-1500 and CMS-1450 (UB-04) PDF templates with claim data."""

from __future__ import annotations

import json
import sys
from pathlib import Path

from pypdf import PdfReader, PdfWriter

try:
    import fitz  # PyMuPDF
except ImportError:  # pragma: no cover
    fitz = None

BASE = Path(__file__).resolve().parent.parent / "public" / "forms"
PUBLIC_JS = Path(__file__).resolve().parent.parent / "public" / "js"
CMS1500_TEMPLATE = BASE / "cms1500-blank.pdf"
UB04_TEMPLATE = BASE / "ub04-blank.pdf"
UB04_POSITIONS = PUBLIC_JS / "ub04-field-positions.json"

INSURANCE_TYPE_MAP = {
    "medicare": "Medicare",
    "medicaid": "Medicaid",
    "tricare": "Tricare",
    "champva": "Champva",
    "group": "Group",
    "feca": "Feca",
    "other": "Other",
}

RELATIONSHIP_MAP = {"self": "S", "spouse": "M", "child": "C", "other": "O"}


def split_date_parts(value: str | None) -> tuple[str, str, str]:
    if not value:
        return "", "", ""
    parts = str(value).replace("/", " ").split()
    if len(parts) >= 3:
        return parts[0][:2], parts[1][:2], parts[2][-2:]
    return "", "", ""


def pick_checked(options: list[dict], default: str = "") -> str:
    for opt in options or []:
        if opt.get("checked"):
            return opt.get("key") or opt.get("label") or default
    return default


def map_hcfa_to_fields(hcfa: dict) -> dict[str, str]:
    fields: dict[str, str] = {}

    fields["insurance_id"] = hcfa.get("box1a_insured_id", "")
    fields["pt_name"] = hcfa.get("box2_patient_name", "")
    fields["ins_name"] = hcfa.get("box4_insured_name", "")

    mm, dd, yy = split_date_parts(hcfa.get("box3_dob"))
    fields["birth_mm"], fields["birth_dd"], fields["birth_yy"] = mm, dd, yy

    addr = hcfa.get("box5_patient_address") or {}
    fields["pt_street"] = addr.get("street", "")
    fields["pt_city"] = addr.get("city", "")
    fields["pt_state"] = addr.get("state", "")
    fields["pt_zip"] = addr.get("zip", "")

    ins_addr = hcfa.get("box7_insured_address") or addr
    fields["ins_street"] = ins_addr.get("street", "")
    fields["ins_city"] = ins_addr.get("city", "")
    fields["ins_state"] = ins_addr.get("state", "")
    fields["ins_zip"] = ins_addr.get("zip", "")

    policy = hcfa.get("box11_insured_policy") or {}
    fields["grp"] = policy.get("group_number", "")
    fields["ins_plan_name"] = policy.get("c_insurance_plan", "")
    fields["ins_policy"] = policy.get("group_number", "")

    ill = hcfa.get("box14_illness_date") or {}
    fields["cur_ill_mm"] = ill.get("mm", "")
    fields["cur_ill_dd"] = ill.get("dd", "")
    fields["cur_ill_yy"] = ill.get("yy", "")

    fields["99icd"] = hcfa.get("box21_icd_indicator", "0")
    fields["prior_auth"] = hcfa.get("box23_prior_auth", "")
    fields["original_ref"] = (hcfa.get("box22_resubmission") or {}).get("original_ref", "")
    fields["medicaid_resub"] = (hcfa.get("box22_resubmission") or {}).get("code", "")

    ref = hcfa.get("box17_referring") or {}
    fields["ref_physician"] = ref.get("name", "")
    fields["physician number 17a"] = ref.get("npi", "")

    fields["pt_signature"] = hcfa.get("box12_patient_signature", "")
    fields["pt_date"] = hcfa.get("box12_date", "")
    fields["ins_signature"] = hcfa.get("box13_insured_signature", "")

    fields["tax_id"] = hcfa.get("box25_tax_id", "")
    fields["pt_account"] = hcfa.get("box26_account", "")
    fields["t_charge"] = hcfa.get("box28_total_charge", "")
    fields["amt_paid"] = hcfa.get("box29_amount_paid", "")

    phys = hcfa.get("box31_physician") or {}
    fields["physician_signature"] = phys.get("signature", "")
    fields["physician_date"] = phys.get("date", "")

    fac = hcfa.get("box32_service_facility") or {}
    fields["fac_name"] = fac.get("name", "")
    fields["fac_street"] = fac.get("address", "")
    fields["fac_location"] = fac.get("npi", "")

    bill = hcfa.get("box33_billing_provider") or {}
    fields["doc_name"] = bill.get("name", "")
    fields["doc_street"] = bill.get("address", "")
    fields["pin"] = bill.get("npi", "")
    phone = bill.get("phone", "")
    if phone:
        digits = "".join(c for c in phone if c.isdigit())
        if len(digits) >= 10:
            fields["doc_phone area"] = digits[:3]
            fields["doc_phone"] = digits[3:]

    fields["NUCC USE"] = hcfa.get("box8_reserved", "")
    fields["Suppl"] = hcfa.get("box19_additional", "")

    for i, dx in enumerate((hcfa.get("box21_diagnoses") or [])[:12], start=1):
        code = (dx.get("code") or "").replace(".", "")
        fields[f"diagnosis{i}"] = code
        if i <= 6:
            fields[f"diag{i}"] = code

    for i, line in enumerate((hcfa.get("box24_lines") or [])[:6], start=1):
        if not line.get("cpt_hcpcs"):
            continue
        fields[f"sv{i}_mm_from"] = line.get("from_mm", "")
        fields[f"sv{i}_dd_from"] = line.get("from_dd", "")
        fields[f"sv{i}_yy_from"] = line.get("from_yy", "")
        fields[f"sv{i}_mm_end"] = line.get("to_mm", "")
        fields[f"sv{i}_dd_end"] = line.get("to_dd", "")
        fields[f"sv{i}_yy_end"] = line.get("to_yy", "")
        fields[f"place{i}"] = line.get("place_of_service", "")
        fields[f"cpt{i}"] = line.get("cpt_hcpcs", "")
        fields[f"mod{i}"] = line.get("modifier_1", "")
        fields[f"mod{i}a"] = line.get("modifier_2", "")
        fields[f"mod{i}b"] = line.get("modifier_3", "")
        fields[f"mod{i}c"] = line.get("modifier_4", "")
        fields[f"plan{i}"] = line.get("diagnosis_pointer", "")
        fields[f"ch{i}"] = line.get("charges", "")
        fields[f"type{i}"] = line.get("units", "")
        fields[f"emg{i}"] = line.get("emg", "")
        local = line.get("rendering_npi", "")
        fields[f"local{i}"] = local[-5:] if local else ""
        fields[f"local{i}a"] = local

    return fields


def map_hcfa_radios(hcfa: dict) -> dict[str, str]:
    radios: dict[str, str] = {}

    ins_key = pick_checked(hcfa.get("box1_insurance_types"), "group")
    radios["insurance_type"] = INSURANCE_TYPE_MAP.get(ins_key, "Group")

    sex = hcfa.get("box3_sex") or {}
    if sex.get("M"):
        radios["sex"] = "M"
    elif sex.get("F"):
        radios["sex"] = "F"

    rel_key = pick_checked(hcfa.get("box6_relationship"), "self")
    radios["rel_to_ins"] = RELATIONSHIP_MAP.get(rel_key, "S")

    cond = hcfa.get("box10_condition") or {}
    emp = cond.get("employment") or {}
    if emp.get("yes"):
        radios["employment"] = "YES"
    elif emp.get("no"):
        radios["employment"] = "NO"

    lab = hcfa.get("box20_outside_lab") or {}
    if lab.get("yes"):
        radios["lab"] = "YES"
    elif lab.get("no"):
        radios["lab"] = "NO"

    assign = hcfa.get("box27_assignment") or {}
    if assign.get("yes"):
        radios["assignment"] = "YES"
    elif assign.get("no"):
        radios["assignment"] = "NO"

    tax_type = hcfa.get("box25_tax_id_type") or {}
    if tax_type.get("ssn"):
        radios["ssn"] = "SSN"
    elif tax_type.get("ein"):
        radios["ssn"] = "EIN"

    another = (hcfa.get("box11_insured_policy") or {}).get("d_another_plan") or {}
    if another.get("yes"):
        radios["ins_benefit_plan"] = "YES"
    elif another.get("no"):
        radios["ins_benefit_plan"] = "NO"

    return radios


def fill_cms1500(payload: dict) -> bytes:
    hcfa = payload.get("hcfa") or {}
    reader = PdfReader(str(CMS1500_TEMPLATE))
    writer = PdfWriter()
    writer.append(reader)
    writer.set_need_appearances_writer()

    text_fields = map_hcfa_to_fields(hcfa)
    radio_fields = map_hcfa_radios(hcfa)
    all_fields = {**text_fields, **radio_fields}
    for key, value in (payload.get("acro_overrides") or {}).items():
        if value is not None and str(value) != "":
            all_fields[key] = str(value)

    for page in writer.pages:
        writer.update_page_form_field_values(page, all_fields, auto_regenerate=False)

    from io import BytesIO

    buf = BytesIO()
    writer.write(buf)
    return buf.getvalue()


def insert_text(page, x: float, y: float, text: str, size: float = 7.5) -> None:
    if not text:
        return
    page.insert_text((x, y), str(text), fontsize=size, fontname="helv", color=(0, 0, 0))


def _selected_code(opts) -> str:
    if not opts:
        return ""
    if isinstance(opts, str):
        return opts
    for item in opts:
        if item.get("selected"):
            return str(item.get("code", ""))
    return ""


def read_ub04_field(ub: dict, key: str) -> str:
    if key == "fl1.name":
        return (ub.get("fl1_billing_provider") or {}).get("name", "")
    if key == "fl1.address":
        return (ub.get("fl1_billing_provider") or {}).get("address", "")
    if key == "fl1.city":
        return (ub.get("fl1_billing_provider") or {}).get("city_state_zip", "")
    if key == "fl1.phone":
        return (ub.get("fl1_billing_provider") or {}).get("phone", "")
    if key == "fl2.payto":
        return ub.get("fl2_pay_to", "")
    if key == "fl3a":
        return ub.get("fl3a_patient_control", "")
    if key == "fl3b":
        return ub.get("fl3b_medical_record", "")
    if key == "fl4":
        return str(ub.get("fl4_type_of_bill", "")).replace("-", "")
    if key == "fl5":
        return ub.get("fl5_fed_tax_id", "")
    if key == "fl6.from":
        return (ub.get("fl6_statement_period") or {}).get("from", "")
    if key == "fl6.through":
        return (ub.get("fl6_statement_period") or {}).get("through", "")
    if key == "fl7":
        return ub.get("fl7", "")
    if key == "fl8":
        return ub.get("fl8_patient_name", "")
    if key == "fl9.street":
        return (ub.get("fl9_patient_address") or {}).get("street", "")
    if key == "fl9.city":
        addr = ub.get("fl9_patient_address") or {}
        return ", ".join(filter(None, [addr.get("city"), addr.get("state"), addr.get("zip")]))
    if key == "fl10.dob":
        return ub.get("fl10_birth_date", "")
    if key == "fl10.sex":
        sex = ub.get("fl10_sex") or {}
        if sex.get("M"):
            return "M"
        if sex.get("F"):
            return "F"
        return ""
    if key == "fl12":
        return ub.get("fl12_admission_date", "")
    if key == "fl13":
        return ub.get("fl13_admission_hour", "")
    if key == "fl14":
        return ub.get("fl14_admission_type_code") or _selected_code(ub.get("fl14_admission_type"))
    if key == "fl15":
        return ub.get("fl15_point_of_origin_code") or _selected_code(ub.get("fl15_point_of_origin"))
    if key == "fl16":
        return ub.get("fl16_discharge_hour", "")
    if key == "fl17":
        return ub.get("fl17_discharge_status_code") or _selected_code(ub.get("fl17_discharge_status"))
    if key == "fl29":
        return ub.get("fl29_accident_state", "")
    if key == "fl30":
        return ub.get("fl30", "")
    if key == "fl37":
        return ub.get("fl37", "")
    if key == "fl47total":
        return ub.get("fl47_total_charges", "")
    if key == "fl48total":
        return ub.get("fl48_non_covered_total", "")
    if key == "fl49total":
        return ub.get("fl49_page_total", "")
    if key == "fl56":
        return ub.get("fl56_billing_provider_npi", "")
    if key == "fl57":
        return ub.get("fl57_other_provider_id", "")
    if key == "fl63":
        return ub.get("fl63_treatment_authorization", "")
    if key == "fl64":
        return ub.get("fl64_document_control_number", "")
    if key == "fl65":
        return ub.get("fl65_employer_name", "")
    if key == "fl66":
        return ub.get("fl66_diagnosis_qualifier", "")
    if key == "fl69":
        return ub.get("fl69_admitting_diagnosis", "")
    if key == "fl70":
        return ub.get("fl70_patient_reason_dx", "")
    if key == "fl71":
        return ub.get("fl71_pps_code", "")
    if key == "fl72":
        return ub.get("fl72_eci", "")
    if key == "fl74.code":
        return (ub.get("fl74_principal_procedure") or {}).get("code", "")
    if key == "fl74.date":
        return (ub.get("fl74_principal_procedure") or {}).get("date", "")
    if key == "fl76.qualifier":
        return (ub.get("fl76_attending") or {}).get("qualifier", "")
    if key == "fl76.npi":
        return (ub.get("fl76_attending") or {}).get("npi", "")
    if key == "fl76.name":
        return (ub.get("fl76_attending") or {}).get("name", "")
    if key == "fl80":
        return ub.get("fl80_remarks", "")

    if key.startswith("fl") and len(key) in (3, 4) and key[2:].isdigit():
        idx = int(key[2:]) - 18
        if 0 <= idx <= 10:
            rows = ub.get("fl18_28_condition_codes") or []
            if idx < len(rows):
                return rows[idx].get("code", "")
            return ""

    if key.startswith("fl31_34."):
        parts = key.split(".")
        if len(parts) == 3:
            idx = int(parts[1])
            rows = ub.get("fl31_34_occurrence_codes") or []
            if idx < len(rows):
                return rows[idx].get(parts[2], "")
        return ""

    if key.startswith("fl35_36."):
        parts = key.split(".")
        if len(parts) == 3:
            idx = int(parts[1])
            rows = ub.get("fl35_36_occurrence_span") or []
            if idx < len(rows):
                return rows[idx].get(parts[2], "")
        return ""

    if key.startswith("fl39_41."):
        parts = key.split(".")
        if len(parts) == 3:
            idx = int(parts[1])
            rows = ub.get("fl39_41_value_codes") or []
            if idx < len(rows):
                return rows[idx].get(parts[2], "")
        return ""

    if key.startswith("fl50."):
        parts = key.split(".")
        if len(parts) == 3:
            idx = int(parts[1])
            payers = ub.get("fl50_payers") or []
            payer = payers[idx] if idx < len(payers) else {}
            return payer.get(parts[2], "")

    if key.startswith("fl58.") or key.startswith("fl59.") or key.startswith("fl60.") or key.startswith("fl61.") or key.startswith("fl62."):
        parts = key.split(".")
        if len(parts) == 3:
            fl = int(parts[0][2:])
            idx = int(parts[1])
            field_map = {58: "name", 59: "rel", 60: "id", 61: "group_name", 62: "group_no"}
            rows = ub.get("fl58_62_insured") or []
            row = rows[idx] if idx < len(rows) else {}
            return row.get(field_map.get(fl, parts[2]), "")

    if key.startswith("fl67."):
        idx = int(key.split(".")[1])
        dxs = ub.get("fl67_diagnoses") or []
        if idx < len(dxs):
            return str(dxs[idx].get("code", "")).replace(".", "")
        return ""

    if key.startswith("fl75."):
        parts = key.split(".")
        if len(parts) == 3:
            idx = int(parts[1])
            rows = ub.get("fl75_other_procedures") or []
            if idx < len(rows):
                return rows[idx].get(parts[2], "")
        return ""

    if key.startswith("fl77_79."):
        parts = key.split(".")
        if len(parts) == 3:
            idx = int(parts[1])
            rows = ub.get("fl77_79_providers") or []
            if idx < len(rows):
                return rows[idx].get(parts[2], "")
        return ""

    if key.startswith("line") and "." in key:
        line_key, field = key.split(".", 1)
        idx = int(line_key.replace("line", ""))
        lines = ub.get("fl42_47_lines") or []
        if idx >= len(lines):
            return ""
        line = lines[idx]
        if field == "fl45":
            return line.get("fl45_service_date") or line.get("fl45_date") or ""
        if field == "fl46":
            return line.get("fl46") or line.get("fl45", "")
        if field == "fl47":
            return line.get("fl47") or line.get("fl46", "")
        if field == "fl48":
            return line.get("fl48", "")
        return line.get(field, "")

    return ""


def fill_ub04(payload: dict) -> bytes:
    if fitz is None:
        raise RuntimeError("PyMuPDF (pymupdf) is required for UB-04 PDF generation.")

    ub = payload.get("ub04") or {}
    doc = fitz.open(str(UB04_TEMPLATE))
    page = doc[0]
    pw, ph = page.rect.width, page.rect.height

    positions = json.loads(UB04_POSITIONS.read_text(encoding="utf-8"))
    for field in positions.get("fields", []):
        key = field.get("key", "")
        value = read_ub04_field(ub, key)
        if not value:
            continue
        x = field["x"] / 100 * pw + 2
        y = field["y"] / 100 * ph + 2
        size = 6.5 if "fl43" in key or key == "fl80" else 7.5
        if key == "fl4":
            size = 8
        insert_text(page, x, y, str(value), size)

    out = doc.tobytes()
    doc.close()
    return out


def main() -> None:
    import argparse

    parser = argparse.ArgumentParser()
    parser.add_argument("--input", help="JSON payload file")
    parser.add_argument("--output", help="Write PDF to this file instead of stdout")
    args, _ = parser.parse_known_args()

    if args.input:
        raw = Path(args.input).read_text(encoding="utf-8")
    else:
        raw = sys.stdin.read()

    payload = json.loads(raw)
    form_type = (payload.get("form_type") or "").lower()

    if form_type == "hcfa":
        pdf = fill_cms1500(payload)
    elif form_type == "ub04":
        pdf = fill_ub04(payload)
    else:
        raise SystemExit(f"Unsupported form_type: {form_type}")

    if args.output:
        Path(args.output).write_bytes(pdf)
    else:
        sys.stdout.buffer.write(pdf)


if __name__ == "__main__":
    main()
