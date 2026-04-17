# -*- coding: utf-8 -*-
"""Regenerate faq_body.blade.php from MPACK policy markdown (UTF-8)."""
from __future__ import annotations

import re
from pathlib import Path

MD_PATH = Path(r"C:\Users\Ray\Downloads\chinh_sach_MPACK_dieu_khoan_phap_ly.md")
RESTORE_PATH = Path(__file__).resolve().parent.parent / "_faq_restore.blade.php"
OUT_PATH = (
    Path(__file__).resolve().parent.parent
    / "Modules/Cms/Resources/views/frontend/pages/partials/decor/faq_body.blade.php"
)

# Vietnamese UI strings (ASCII-safe source file)
T_LEGAL = "L\u01b0u \u00fd ph\u00e1p l\u00fd"
T_1 = "1. Th\xf4ng tin ph\xe1p nh\xe2n v\xe0 ph\u1ea1m vi kinh doanh"
T_22 = "2.1\u20132.2. M\u1ee5c \u0111\xedch v\xe0 ph\u1ea1m vi \xe1p d\u1ee5ng"
T_24 = "2.3\u20132.4. Th\u1ee9 t\u1ef1 \u01b0u ti\xean v\xe0 \u0111\u1ed1i t\u01b0\u1ee3ng \xe1p d\u1ee5ng"
T_3 = "3. Gi\u1ea3i th\xedch t\u1eeb ng\u1eef"
T_4 = "4. Nguy\u00ean t\u1eafc giao k\u1ebft v\xe0 x\xe1c l\u1eadp giao d\u1ecbch"
T_MERGE_67 = (
    "6.6\u20136.7. Ch\u1eadm thanh to\xe1n, x\u1eed l\xfd c\xf4ng n\u1ee3 v\xe0 h\xf3a \u0111\u01a1n"
)
T_MERGE_77 = "7.6\u20137.7. Chi ph\xed \u0111\u1ed5i/tr\u1ea3 v\xe0 ho\xe0n ti\u1ec1n"
T_MERGE_87 = (
    "8.6\u20138.7. Ch\u1eadm giao h\xe0ng v\xe0 chuy\u1ec3n giao r\u1ee7i ro"
)
T_TAIL = "10\u201317. Khi\u1ebfu n\u1ea1i, b\u1ea3o m\u1eadt v\xe0 c\xe1c \u0111i\u1ec1u kho\u1ea3n chung kh\xe1c"
T_HOME = "Trang ch\u1ee7"
T_HERO = "Ch\xednh s\xe1ch &amp; \u0111i\u1ec1u kho\u1ea3n MPACK"
T_CRUMB = "Ch\xednh s\xe1ch &amp; \u0111i\u1ec1u kho\u1ea3n"
T_NAV6 = "H\u1ed7 tr\u1ee3 &amp; \u0111i\u1ec1u kho\u1ea3n kh\xe1c"


def get_section_body(md: str, section_num: int) -> str:
    m = re.search(rf"^## {section_num}\.\s+[^\n]+\n", md, re.M)
    if not m:
        raise ValueError(f"Missing section {section_num}")
    start = m.end()
    m2 = re.search(r"^## \d+\.\s+", md[start:], re.M)
    end = start + m2.start() if m2 else len(md)
    return md[start:end].strip()


def subsections(sec: str) -> list[tuple[str, str]]:
    parts = re.split(r"(?m)^###\s+", sec)
    out: list[tuple[str, str]] = []
    for p in parts:
        p = p.strip()
        if not p:
            continue
        title, _, body = p.partition("\n")
        title = title.strip()
        body = body.strip()
        if title or body:
            out.append((title, body))
    return out


def inline_bold(s: str) -> str:
    return re.sub(r"\*\*(.+?)\*\*", r"<strong>\1</strong>", s)


def md_to_html(fragment: str) -> str:
    text = fragment.strip()
    if not text:
        return ""
    out: list[str] = []
    lines = text.split("\n")
    i = 0
    while i < len(lines):
        raw = lines[i].rstrip()
        if not raw:
            i += 1
            continue
        if raw.strip() == "---":
            i += 1
            continue
        if raw.startswith("## "):
            out.append(f"<p><strong>{inline_bold(raw[3:].strip())}</strong></p>")
            i += 1
            continue
        if raw.startswith("### "):
            out.append(f"<p><strong>{inline_bold(raw[4:])}</strong></p>")
            i += 1
            continue
        if raw.startswith("- "):
            items: list[str] = []
            while i < len(lines) and lines[i].strip() and lines[i].lstrip().startswith("- "):
                items.append(lines[i].lstrip()[2:].rstrip())
                i += 1
            lis = "\n".join(f"                            <li>{inline_bold(x)}</li>" for x in items)
            out.append(f"<ul>\n{lis}\n</ul>")
            continue
        if re.match(r"^\d+\.\s+", raw):
            items = []
            while i < len(lines) and lines[i].strip() and re.match(r"^\d+\.\s+", lines[i].lstrip()):
                items.append(re.sub(r"^\d+\.\s+", "", lines[i].strip()))
                i += 1
            lis = "\n".join(f"                            <li>{inline_bold(x)}</li>" for x in items)
            out.append(f"<ol>\n{lis}\n</ol>")
            continue
        para_lines: list[str] = []
        while i < len(lines) and lines[i].strip():
            ln = lines[i].rstrip()
            if ln.strip() == "---":
                break
            if ln.startswith("###") or ln.startswith("- ") or re.match(r"^\d+\.\s+", ln):
                break
            para_lines.append(ln)
            i += 1
        if para_lines and all("*" in ln for ln in para_lines):
            joined = "<br>".join(inline_bold(x.strip()) for x in para_lines)
            out.append(f"<p>{joined}</p>")
        else:
            out.append(f"<p>{inline_bold(' '.join(para_lines))}</p>")
    return "\n                                                        ".join(out)


def format_legal_note(md: str) -> str:
    m = re.search(r"^> \*\*(.+?)\*\* (.+)$", md, re.M)
    if not m:
        raise ValueError("Legal note not found")
    return f"<p><strong>{m.group(1)}</strong> {inline_bold(m.group(2))}</p>"


def tail_from_section_10(md: str) -> str:
    m = re.search(r"^## 10\.\s+[^\n]+\n", md, re.M)
    if not m:
        raise ValueError("Section 10 not found")
    return md[m.end() :].strip()


def accordion_items(
    parent: str,
    pairs: list[tuple[str, str]],
    *,
    md: str,
    first_fs18: bool = False,
) -> str:
    chunks: list[str] = []
    for idx, (title, body_md) in enumerate(pairs):
        n = idx + 1
        acc_id = f"{parent}-{n:02d}"
        is_first = idx == 0
        is_last = idx == len(pairs) - 1
        fs = "fs-18" if (is_first and first_fs18) else "fs-17"
        if is_first:
            hb = "accordion-header border-bottom border-color-extra-medium-gray pt-0"
        elif is_last:
            hb = "accordion-header border-bottom border-color-transparent"
        elif idx == 1:
            hb = "accordion-header border-bottom border-color-extra-medium-gray"
        else:
            hb = "accordion-header border-bottom border-color-light-medium-gray"
        bb = "border-bottom border-color-transparent" if is_last else "border-bottom border-color-light-medium-gray"
        active = " active-accordion" if is_first else ""
        collapse = "accordion-collapse collapse show" if is_first else "accordion-collapse collapse"
        icon = "icon-feather-minus" if is_first else "icon-feather-plus"
        aria = "true" if is_first else "false"
        if parent == "01" and idx == 0:
            inner = format_legal_note(md)
        else:
            inner = md_to_html(body_md)
        chunks.append(
            f"""                                            <!-- start accordion item -->
                                            <div class="accordion-item{active}">
                                                <div class="{hb}">
                                                    <a href="#" data-bs-toggle="collapse" data-bs-target="#accordion-style-{acc_id}" aria-expanded="{aria}" data-bs-parent="#accordion-style-{parent}">
                                                        <div class="accordion-title mb-0 position-relative text-dark-gray">
                                                            <i class="feather {icon}"></i><span class="fw-500 {fs}">{title}</span>
                                                        </div>
                                                    </a>
                                                </div>
                                                <div id="accordion-style-{acc_id}" class="{collapse}" data-bs-parent="#accordion-style-{parent}">
                                                    <div class="accordion-body last-paragraph-no-margin {bb}">
                                                        {inner}
                                                    </div>
                                                </div>
                                            </div>"""
        )
    return "\n".join(chunks)


def replace_accordion_inner(content: str, acc_parent: str, new_body: str) -> str:
    opener = f'<div class="accordion accordion-style-02" id="accordion-style-{acc_parent}"'
    i0 = content.index(opener)
    i1 = content.index(">", i0) + 1
    tab_end = content.index("<!-- end tab content -->", i0)
    chunk = content[i0:tab_end]
    last_item = chunk.rfind("                                            <!-- end accordion item -->")
    if last_item == -1:
        raise ValueError(f"No accordion items for {acc_parent}")
    j = i0 + last_item + len("                                            <!-- end accordion item -->")
    k = content.index("\n                                        </div>", j - 1)
    return content[:i1] + "\n" + new_body + content[k:]


def main() -> None:
    md = MD_PATH.read_text(encoding="utf-8")
    restore = RESTORE_PATH.read_text(encoding="utf-8")

    sec1 = get_section_body(md, 1)
    sec2 = get_section_body(md, 2)
    sec2_12 = sec2[sec2.index("### 2.1.") : sec2.index("### 2.3.")].strip()
    sec2_34 = sec2[sec2.index("### 2.3.") :].strip()
    sec3 = get_section_body(md, 3)
    sec4 = get_section_body(md, 4)
    sec5 = get_section_body(md, 5)
    sec6 = get_section_body(md, 6)
    sec7 = get_section_body(md, 7)
    sec8 = get_section_body(md, 8)
    sec9 = get_section_body(md, 9)
    tail_10_17 = tail_from_section_10(md)

    s5 = subsections(sec5)
    if len(s5) != 6:
        raise SystemExit(f"Expected 6 subsections in section 5, got {len(s5)}")

    s6 = subsections(sec6)
    if len(s6) != 7:
        raise SystemExit(f"Expected 7 subsections in section 6, got {len(s6)}")
    tab3_pairs = s6[:5] + [
        (
            T_MERGE_67,
            f"### {s6[5][0]}\n{s6[5][1]}\n\n### {s6[6][0]}\n{s6[6][1]}",
        )
    ]

    s7 = subsections(sec7)
    if len(s7) != 7:
        raise SystemExit(f"Expected 7 subsections in section 7, got {len(s7)}")
    tab4_pairs = s7[:5] + [
        (
            T_MERGE_77,
            f"### {s7[5][0]}\n{s7[5][1]}\n\n### {s7[6][0]}\n{s7[6][1]}",
        )
    ]

    s8 = subsections(sec8)
    if len(s8) != 7:
        raise SystemExit(f"Expected 7 subsections in section 8, got {len(s8)}")
    tab5_pairs = s8[:5] + [
        (
            T_MERGE_87,
            f"### {s8[5][0]}\n{s8[5][1]}\n\n### {s8[6][0]}\n{s8[6][1]}",
        )
    ]

    s9 = subsections(sec9)
    if len(s9) != 5:
        raise SystemExit(f"Expected 5 subsections in section 9, got {len(s9)}")
    tab6_pairs = s9 + [(T_TAIL, tail_10_17)]

    tab1_pairs = [
        (T_LEGAL, ""),
        (T_1, sec1),
        (T_22, sec2_12),
        (T_24, sec2_34),
        (T_3, sec3),
        (T_4, sec4),
    ]

    out = restore
    repls = [
        ("01", accordion_items("01", tab1_pairs, md=md, first_fs18=True)),
        ("02", accordion_items("02", s5, md=md)),
        ("03", accordion_items("03", tab3_pairs, md=md)),
        ("04", accordion_items("04", tab4_pairs, md=md)),
        ("05", accordion_items("05", tab5_pairs, md=md)),
        ("06", accordion_items("06", tab6_pairs, md=md)),
    ]
    for parent, body in repls:
        out = replace_accordion_inner(out, parent, body)

    out = out.replace(">FAQs</h1>", f">{T_HERO}</h1>")
    out = out.replace(">Home</a></li>", f">{T_HOME}</a></li>")
    out = out.replace("<li>FAQs</li>", f"<li>{T_CRUMB}</li>")
    out = out.replace(">General</span>", ">\u0110i\u1ec1u kho\u1ea3n chung</span>")
    out = out.replace(">Shopping information</span>", ">Ch\xednh s\xe1ch mua h\xe0ng</span>")
    out = out.replace(">Payment information</span>", ">\u0110i\u1ec1u kho\u1ea3n thanh to\xe1n</span>")
    out = out.replace(">Orders and returns</span>", ">Ho\xe0n tr\u1ea3 h\xe0ng h\xf3a</span>")
    out = out.replace(">Ordering from crafto</span>", ">Ch\xednh s\xe1ch giao h\xe0ng</span>")
    out = out.replace(">Help and support</span>", f">{T_NAV6}</span>")

    OUT_PATH.write_text(out, encoding="utf-8")
    print("Wrote", OUT_PATH)


if __name__ == "__main__":
    main()
