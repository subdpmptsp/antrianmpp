<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $mppBranding['name'] }} — Membuka Struk</title>
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
        
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            text-align: center;
            max-width: 400px;
            width: 100%;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .subtitle {
            font-size: 16px;
            color: #666;
            margin-bottom: 20px;
        }
        
        .queue-info {
            background: #e3f2fd;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .queue-number {
            font-size: 28px;
            font-weight: bold;
            color: #1976d2;
            margin: 10px 0;
        }
        
        .service-info {
            font-size: 14px;
            color: #666;
            margin: 5px 0;
        }
        
        .manual-link {
            margin-top: 30px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            border: 1px solid #dee2e6;
        }
        
        .manual-link a {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
        }
        
        .manual-link a:hover {
            text-decoration: underline;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="spinner"></div>
        
        <div class="title">Membuka Struk PDF...</div>
        <div class="subtitle">Mohon tunggu sebentar</div>
        
        <div class="queue-info">
            <div class="service-info">Layanan: {{ $service->name }}</div>
            <div class="queue-number">{{ $queue->number }}</div>
            <div class="service-info">Nomor Antrian Anda</div>
        </div>
        
        <div class="manual-link">
            <p>Jika PDF tidak terbuka otomatis, klik link di bawah ini:</p>
            <a href="{{ $pdfUrl }}" target="_blank" id="pdfLink">Buka Struk PDF</a>
        </div>
        
        <div class="error-message" id="errorMessage">
            <p>Gagal membuka PDF. Silakan klik link di atas untuk membuka secara manual.</p>
        </div>
    </div>

    <script>
        const pdfUrl = '{{ $pdfUrl }}';
        const pdfLink = document.getElementById('pdfLink');
        const errorMessage = document.getElementById('errorMessage');
        
        console.log('PDF URL:', pdfUrl);
        
        // Coba buka PDF otomatis
        function openPDF() {
            try {
                // Method 1: Direct window.open
                const newWindow = window.open(pdfUrl, '_blank');
                
                if (newWindow) {
                    console.log('PDF opened successfully');
                    // Tutup halaman redirect setelah 3 detik
                    setTimeout(() => {
                        window.close();
                    }, 3000);
                } else {
                    throw new Error('Popup blocked');
                }
            } catch (error) {
                console.error('Error opening PDF:', error);
                showError();
            }
        }
        
        function showError() {
            errorMessage.style.display = 'block';
            document.querySelector('.spinner').style.display = 'none';
            document.querySelector('.title').textContent = 'PDF Tidak Bisa Dibuka Otomatis';
            document.querySelector('.subtitle').textContent = 'Silakan klik link di bawah untuk membuka manual';
        }
        
        // Coba buka PDF setelah halaman load
        window.addEventListener('load', function() {
            setTimeout(openPDF, 1000);
        });
        
        // Fallback: jika user klik link manual
        pdfLink.addEventListener('click', function() {
            console.log('Manual PDF link clicked');
            setTimeout(() => {
                window.close();
            }, 2000);
        });
        
        // Auto-close setelah 10 detik jika tidak ada interaksi
        setTimeout(() => {
            if (document.visibilityState === 'visible') {
                window.close();
            }
        }, 10000);
    </script>
</body>
</html>
