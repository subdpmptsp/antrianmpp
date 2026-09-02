<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $mppBranding['name'] }} — Preview Struk Antrean</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f5f5f5;
        }
        
        .preview-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .preview-title {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        
        .preview-actions {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 0 10px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
        }
        
        .btn:hover {
            background: #0056b3;
        }
        
        .btn-success {
            background: #28a745;
        }
        
        .btn-success:hover {
            background: #1e7e34;
        }
        
        .preview-frame {
            width: 100%;
            height: 600px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .info-box {
            background: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="preview-container">
        <h1 class="preview-title">Preview Struk Antrian PDF</h1>
        
        <div class="info-box">
            <h3>Informasi:</h3>
            <p><strong>Service ID:</strong> {{ $serviceId }}</p>
            <p><strong>URL Preview:</strong> <code>{{ route('struk.preview', ['service_id' => $serviceId]) }}</code></p>
        </div>
        
        <div class="preview-actions">
            <a href="{{ route('struk.preview', ['service_id' => $serviceId]) }}"
               target="_blank" class="btn">
                👁️ Preview PDF
            </a>
        </div>
        
        <iframe 
            src="{{ route('struk.preview', ['service_id' => $serviceId]) }}"
            class="preview-frame">
        </iframe>
    </div>
</body>
</html>
