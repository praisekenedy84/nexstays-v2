<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #0f172a; padding: 18px 22px; }
h1  { font-size: 13pt; font-weight: bold; }
h2  { font-size: 9pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.05em;
      color: #475569; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; margin: 14px 0 6px; }
.meta  { font-size: 7.5pt; color: #64748b; margin: 2px 0 14px; }
table  { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 8.5pt; }
th     { background: #f1f5f9; border: 1px solid #cbd5e1; padding: 5px 7px;
         text-align: left; font-size: 7.5pt; text-transform: uppercase; letter-spacing: 0.04em; color: #475569; }
td     { border: 1px solid #e2e8f0; padding: 4px 7px; }
.tr-total td { background: #f8fafc; font-weight: bold; border-top: 2px solid #94a3b8; }
.tr-section td { background: #eff6ff; font-weight: bold; color: #1d4ed8; }
.r  { text-align: right; }
.c  { text-align: center; }
.green  { color: #15803d; }
.red    { color: #dc2626; }
.amber  { color: #b45309; }
.muted  { color: #64748b; }
.kpis   { width: 100%; border-collapse: separate; border-spacing: 5px; margin-bottom: 14px; }
.kpi    { border: 1px solid #e2e8f0; border-radius: 3px; padding: 7px 9px; background: #f8fafc; }
.kpi-l  { font-size: 7pt; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; }
.kpi-v  { font-size: 12pt; font-weight: bold; margin-top: 1px; }
.footer { margin-top: 16px; padding-top: 6px; border-top: 1px solid #e2e8f0;
          font-size: 7pt; color: #94a3b8; text-align: center; }
</style>
</head>
<body>

<table width="100%" style="border:none; margin-bottom:10px;">
    <tr>
        <td style="border:none; padding:0; vertical-align:bottom;">
            <h1>@yield('title')</h1>
            <p class="meta">Period: @yield('period') &nbsp;·&nbsp; Generated {{ now()->format('d M Y, H:i') }}</p>
        </td>
        <td style="border:none; padding:0; text-align:right; vertical-align:top; font-size:7.5pt; color:#64748b;">
            NexStay
        </td>
    </tr>
</table>

@yield('content')

<p class="footer">NexStay · Exported {{ now()->format('d M Y H:i') }}</p>
</body>
</html>
