<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>{{ $mppBranding['name'] }} — Tiket Antrean</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      text-align: center;
      margin: 0;
      padding: 0;
    }
    .ticket {
      width: 300px;
      border: 1px dashed #000;
      padding: 20px;
      margin: 0 auto;
    }
    .ticket h2 {
      font-size: 16px;
      margin: 5px 0;
    }
    .ticket h3 {
      font-size: 14px;
      margin: 5px 0;
      font-weight: normal;
    }
    .number {
      font-size: 72px;
      font-weight: bold;
      margin: 20px 0;
    }
    .info {
      margin-top: 10px;
      font-size: 14px;
    }
    .info p {
      margin: 4px 0;
    }
  </style>
</head>
<body>
  <div class="ticket">
    <img src="data:image/png;base64,{{ $logo }}" alt="Logo" width="24">
    <h2>MALL PELAYANAN PUBLIK</h2>
    <h2>KOTA SURABAYA</h2>
    <h3>Zona 2 - Loket 2A<br>Layanan SKCK</h3>

    <div class="number">{{$nomor}}</div>

    <div class="info">
      <p><strong>{{$nama}}</strong></p>
      <p>{{$tanggal}}</p>
      <p>Jam Layanan : 09.00 - 14.00</p>
    </div>
  </div>
</body>
</html>
