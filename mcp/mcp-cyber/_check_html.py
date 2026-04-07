from pathlib import Path
import re

html = Path("cyber_api/static/dashboard.html").read_text(encoding="utf-8")
lines = html.count("\n")
print(f"File: {len(html):,} chars, {lines} lines")
print("tool-sidebar present:", "tool-sidebar" in html)
print("MCP_TOOLS present:", "MCP_TOOLS" in html)
print("sbRenderTools present:", "sbRenderTools" in html)
print("page-wrap present:", "page-wrap" in html)
print("dash-main present:", "dash-main" in html)
print("sidebar-toggle-btn present:", "sidebar-toggle-btn" in html)
tool_defs = re.findall(r'name:"[a-z_]+"', html)
print(f"Tool definitions: {len(tool_defs)}")
for t in tool_defs:
    print(f"  {t}")
