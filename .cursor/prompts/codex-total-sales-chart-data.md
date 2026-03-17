# Codex prompt: Wire real sales data to Total Sales chart (no UI changes)

Use this prompt when asking Codex (or another agent) to add all related sales data to the Total Sales chart.

---

## Task

Add **real sales data** from the codebase to the **Total Sales** card on the dashboard so the chart shows actual business sales. **Do not change the chart UI or styling**—only change where the chart gets its data (backend + data passed to JS).

## Scope

- **Blade:** `resources/views/home/index.blade.php` — Total Sales card only (approx. lines 425–466).
- **Chart element:** `#ecommerceTotalSalesChart` (ApexCharts area chart).
- **Chart init:** `public/assets/js/custom.js` — block that creates the chart for `ecommerceTotalSalesChart` (approx. lines 189–316). Same block may exist in `src/assets/js/custom.js`; keep in sync if the project builds from `src/` to `public/`.

## Constraints (mandatory)

1. **Do not change chart UI/style.** Keep the existing ApexCharts options exactly for:
   - `chart.type` ("area"), `height` (365), `zoom`, `colors`, `dataLabels`, `stroke`, `grid`, `fill`, `xaxis`/`yaxis` styling, `legend`, `tooltip`.
2. **Only replace the data** used for:
   - `series[].name` and `series[].data`
   - `xaxis.categories`
   - Optionally `yaxis.max`/`min` or tooltip formatter if the real values need different scale (e.g. actual currency instead of “$Xk”). Prefer keeping the same visual style (e.g. still format as “$Xk” or similar) so the UI looks unchanged.

## Current (hardcoded) data shape

- **Series:** Two series: "Current Sale" and "Last Year Sale", each with 12 values (one per month).
- **Categories:** `["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"]`.
- **Values:** Numbers (e.g. 35, 50, 55, …); chart shows them as `$Xk` in tooltip/y-axis.

## Data source in codebase

- **Controller:** `app/Http/Controllers/HomeController.php` — already loads sales for the dashboard:
  - Uses `$this->transactionUtil->getSellsCurrentFy($business_id, $start, $end)` for sales in a date range.
  - Builds `sells_chart_1` (last 30 days) and `sells_chart_2` (FY by month) from the same data. Use the same or similar logic for **Total Sales** (e.g. monthly totals for current year + previous year).
- **Existing helpers:** `TransactionUtil::getSellsCurrentFy`, `getSellTotals`, etc. Use these (or the same query pattern) to get:
  - **Monthly:** Sum of sales per month for current year and previous year (to map to "Current Sale" / "Last Year Sale").
  - **Weekly / Yearly:** If the Total Sales dropdown (Weekly / Monthly / Yearly) is to be wired, compute the same structure for the selected period (e.g. week labels + two series, or 12 months, or yearly bars) and keep the same ApexCharts options so the look and feel stay the same.

## Implementation requirements

1. **Backend**
   - In `HomeController` (or a small dedicated method), compute time-series sales data for the Total Sales chart:
     - At minimum: **monthly** totals for **current year** and **previous year** (to match the two existing series), with month labels (e.g. Jan–Dec).
     - Respect **dashboard filters** if the page already uses them: `dashboard_location`, `dashboard_date_filter` (start/end). If those are used elsewhere on the same page, pass the same `location_id` and date range into this data so the Total Sales chart is consistent with the rest of the dashboard.
   - Pass this data to the view (e.g. a single variable like `$total_sales_chart_data` with structure: `categories`, `current_year_values`, `last_year_values`, and optionally `currency_code` or scale hint for formatting).

2. **View (Blade)**
   - In `resources/views/home/index.blade.php`, only in the Total Sales card section (around 425–466):
     - Expose the backend data to JavaScript in a way that the chart script can read it (e.g. a `<script type="application/json" id="total-sales-chart-data">` with JSON, or a global like `window.totalSalesChartData`). Do not change the existing card HTML/CSS or the `#ecommerceTotalSalesChart` container structure/classes.

3. **JavaScript**
   - In `public/assets/js/custom.js` (and `src/assets/js/custom.js` if applicable), in the block that initializes `#ecommerceTotalSalesChart`:
     - Replace the hardcoded `series` and `xaxis.categories` with data from the backend (from the JSON or global set in the Blade view). Keep every other option (chart type, height, colors, stroke, grid, fill, axis style, legend, tooltip) unchanged so the chart looks the same.
   - If the Total Sales dropdown (Weekly / Monthly / Yearly) is present in the card:
     - Either (a) load all three datasets on page load and switch `series`/`categories` when the user changes the dropdown, or (b) add an AJAX endpoint that returns data for the selected period and refresh the chart when the dropdown changes. In both cases, keep the same ApexCharts configuration so the UI style does not change.

## Acceptance criteria

- The Total Sales chart shows **real sales** from the database (same business and, if applicable, location/date filters as the rest of the dashboard).
- Chart appearance (area chart, colors, height, axes, legend, tooltips) is **unchanged**.
- No new chart libraries or new CSS for this card; only data flow and possibly one small backend endpoint or extended controller data.
- Existing dashboard behavior (e.g. `sells_chart_1`, `sells_chart_2`, location/date filters) continues to work.

---

**Summary one-liner for Codex:**  
Wire real sales data from HomeController/TransactionUtil into the Total Sales ApexCharts chart (`#ecommerceTotalSalesChart`) in `home/index.blade.php` and `custom.js`. Do not change any chart UI or styling—only replace series/categories with backend-driven data (e.g. current year vs last year monthly), and optionally support the Weekly/Monthly/Yearly dropdown with the same chart style.
