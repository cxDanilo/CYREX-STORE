<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; margin: 0; padding: 32px; }
  table { width: 100%; border-collapse: collapse; }

  .header-table td { vertical-align: middle; }
  .title { color: {{ $bannerColor }}; font-size: 22px; font-weight: bold; letter-spacing: .5px; margin: 0; }
  .subtitle { color: #888; font-size: 10.5px; margin: 4px 0 0; }

  .meta-table { margin-top: 20px; }
  .meta-table td { padding: 3px 0; font-size: 11px; color: #555; }
  .meta-table .label { font-weight: bold; color: #333; }
  .meta-table .right { text-align: right; }

  .items-box { margin-top: 22px; border: 1px solid #ECECEC; border-radius: 12px; padding: 2px; }
  .watermark-wrap { text-align: center; margin-top: 26px; }
  .watermark { width: 260px; opacity: 0.07; }
  .items-table th { background: {{ $bannerColorLight }}; color: #333; padding: 10px 14px; font-size: 9.5px; text-transform: uppercase; letter-spacing: .6px; text-align: left; }
  .items-table th:first-child { border-top-left-radius: 10px; }
  .items-table th:last-child { border-top-right-radius: 10px; }
  .items-table td { padding: 10px 14px; font-size: 10.5px; border-top: 1px solid #F2F2F2; }
  .items-table .num { color: {{ $bannerColor }}; font-weight: bold; }

  .totals-table { margin-top: 10px; }
  .totals-table td { padding: 4px 14px; font-size: 11px; color: #777; }
  .totals-table .grand td { padding-top: 8px; font-size: 15px; font-weight: bold; color: {{ $bannerColor }}; }
  .totals-table .right { text-align: right; }

  .footer-box { margin-top: 26px; border: 1px solid #ECECEC; border-radius: 12px; background: #FAFAFA; padding: 16px 20px; }
  .footer-box p { margin: 0 0 6px; font-size: 10px; color: #555; }
  .footer-box p:last-child { margin-bottom: 0; }
  .check { color: {{ $bannerColor }}; font-weight: bold; }
  .thanks { text-align: center; font-weight: bold; margin-top: 16px; font-size: 11px; color: #333; letter-spacing: .3px; }
</style>
</head>
<body>

  <table class="header-table">
    <tr>
      <td width="55%"><img src="{{ $logoFullPath }}" style="max-height:56px;"></td>
      <td width="45%" style="text-align:right;">
        <p class="title">{{ $bannerText }}</p>
        <p class="subtitle">{{ $date }}</p>
      </td>
    </tr>
  </table>

  <table class="meta-table">
    <tr>
      <td width="55%"><span class="label">Cliente:</span> ______________________________</td>
      <td width="45%" class="right"><span class="label">Cotización N°</span> {{ $quoteNumber }}</td>
    </tr>
    <tr>
      <td><span class="label">Teléfono:</span> ______________________________</td>
      <td class="right"><span class="label">Cel/WhatsApp</span> {{ $whatsappNumber }}</td>
    </tr>
    <tr>
      <td><span class="label">Asesor:</span> ______________________________</td>
      <td></td>
    </tr>
  </table>

  <div class="items-box">
    <table class="items-table">
      <thead>
        <tr>
          <th width="6%">N°</th>
          <th width="48%">Producto</th>
          <th width="10%">Cant.</th>
          <th width="18%">Precio unit.</th>
          <th width="18%">Subtotal</th>
        </tr>
      </thead>
      <tbody>
        @foreach($lines as $i => $line)
          <tr>
            <td class="num">{{ $i + 1 }}</td>
            <td>{{ $line['description'] }} — {{ $line['name'] }}</td>
            <td>{{ $line['qty'] }}</td>
            <td>${{ number_format($line['unit_price'], 2) }}</td>
            <td>${{ number_format($line['total'], 2) }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <table class="totals-table">
    <tr>
      <td width="82%" class="right">Subtotal</td>
      <td width="18%">${{ number_format($subtotal, 2) }}</td>
    </tr>
    <tr class="grand">
      <td class="right">Total</td>
      <td>${{ number_format($total, 2) }}</td>
    </tr>
  </table>

  <div class="watermark-wrap">
    <img class="watermark" src="{{ $logoFullPath }}">
  </div>

  <div class="footer-box">
    <p><span class="check">✓</span> Garantía Cyrex: 1 año en PCs completas y 3 meses en piezas sueltas.</p>
    <p><span class="check">✓</span> Métodos de pago: efectivo, QR y transferencia.</p>
    <p><span class="check">✓</span> Precios sujetos a cambio. Cotización válida por 24hs.</p>
  </div>

  <p class="thanks">¡GRACIAS POR PREFERIRNOS!</p>

</body>
</html>
