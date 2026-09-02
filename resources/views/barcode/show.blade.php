<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $mppBranding['name'] }} — Barcode Antrean {{ $queueNumber }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .barcode-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        
        .header {
            margin-bottom: 30px;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .title {
            font-size: 18px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .subtitle {
            font-size: 14px;
            color: #999;
        }
        
        .qr-section {
            margin: 30px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 15px;
            border: 2px dashed #dee2e6;
        }
        
        .qr-code {
            margin: 20px 0;
        }
        
        .qr-code svg {
            max-width: 100%;
            height: auto;
        }
        
        .scan-instruction {
            font-size: 16px;
            color: #495057;
            margin: 20px 0;
            font-weight: 500;
        }
        
        .queue-info {
            background: #e3f2fd;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .queue-number {
            font-size: 32px;
            font-weight: bold;
            color: #1976d2;
            margin: 10px 0;
        }
        
        .service-info {
            font-size: 14px;
            color: #666;
            margin: 5px 0;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .footer-info {
            font-size: 12px;
            color: #999;
            margin: 5px 0;
        }
        
        .back-button {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 20px;
            text-decoration: none;
            display: inline-block;
        }
        
        .back-button:hover {
            background: #5a6268;
        }
        
        @media (max-width: 480px) {
            .barcode-container {
                padding: 20px;
                margin: 10px;
            }
            
            .queue-number {
                font-size: 24px;
            }
            
            .qr-section {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="barcode-container">
        <div class="header">
            <div class="logo">🏛️ MALL PELAYANAN PUBLIK</div>
            <div class="title">KOTA SURABAYA</div>
            <div class="subtitle">{{ $zona }} - {{ $instansiName }}</div>
        </div>
        
        <div class="qr-section">
            <div class="scan-instruction">
                📱 Scan barcode dengan HP untuk mendapatkan struk PDF
            </div>
            
            <div class="qr-code">
                {!! $qrCode !!}
            </div>
            
            <div class="scan-instruction">
                Arahkan kamera HP ke barcode di atas
            </div>
            
            <div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 5px; font-size: 12px; color: #666;">
                <strong>URL untuk testing:</strong><br>
                <code style="word-break: break-all;">{{ $scanUrl }}</code>
            </div>
        </div>
        
        <div class="queue-info">
            <div class="service-info">Layanan: {{ $serviceName }}</div>
            <div class="queue-number">{{ $queueNumber }}</div>
            <div class="service-info">Nomor Antrian Anda</div>
        </div>
        
        <div class="footer">
            <div class="footer-info">Tanggal: {{ $tanggal }}</div>
            <div class="footer-info">Waktu: {{ $waktu }}</div>
            <div class="footer-info">Status: Menunggu</div>
        </div>
        
        <a href="javascript:history.back()" class="back-button">
            ← Kembali
        </a>
    </div>
</body>
</html>
