param(
    [switch]$SkipDependencies,
    [switch]$SkipAssets,
    [switch]$SkipMigrations,
    [switch]$SkipBenchmark,
    [ValidateRange(1, 50)]
    [int]$BenchmarkRuns = 3,
    [ValidateRange(1, 60000)]
    [int]$MaxAverageMs = 250,
    [ValidateRange(1, 10000)]
    [int]$MaxQueries = 15
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location -LiteralPath $projectRoot
$maintenanceEnabled = $false

function Invoke-CheckedCommand {
    param(
        [Parameter(Mandatory = $true)]
        [string]$FilePath,

        [Parameter(Mandatory = $true)]
        [string[]]$ArgumentList,

        [Parameter(Mandatory = $true)]
        [string]$Description
    )

    & $FilePath @ArgumentList

    if ($LASTEXITCODE -ne 0) {
        throw "$Description gagal dengan exit code $LASTEXITCODE."
    }
}

if (-not (Test-Path -LiteralPath (Join-Path $projectRoot 'artisan'))) {
    throw 'artisan tidak ditemukan. Jalankan script dari repository aplikasi.'
}

Invoke-CheckedCommand -FilePath 'php' -ArgumentList @('artisan', 'optimize:clear') -Description 'Pembersihan cache'
Invoke-CheckedCommand -FilePath 'php' -ArgumentList @(
    '-r',
    "exit(extension_loaded('Zend OPcache') && filter_var(ini_get('opcache.enable'), FILTER_VALIDATE_BOOLEAN) ? 0 : 1);"
) -Description 'Validasi PHP OPcache'
Invoke-CheckedCommand -FilePath 'php' -ArgumentList @('artisan', 'app:production-audit') -Description 'Audit konfigurasi produksi'
Invoke-CheckedCommand -FilePath 'php' -ArgumentList @('artisan', 'app:operator-password-audit') -Description 'Audit rotasi password petugas'

try {
    Invoke-CheckedCommand -FilePath 'php' -ArgumentList @('artisan', 'down', '--retry=60') -Description 'Aktivasi mode maintenance'
    $maintenanceEnabled = $true

    if (-not $SkipDependencies) {
        Invoke-CheckedCommand -FilePath 'composer' -ArgumentList @('install', '--no-dev', '--optimize-autoloader', '--no-interaction') -Description 'Instalasi dependency PHP'
    }

    if (-not $SkipAssets) {
        Invoke-CheckedCommand -FilePath 'npm' -ArgumentList @('ci') -Description 'Instalasi dependency frontend'
        Invoke-CheckedCommand -FilePath 'npm' -ArgumentList @('run', 'build') -Description 'Build aset frontend'
    }

    if (-not $SkipMigrations) {
        Invoke-CheckedCommand -FilePath 'php' -ArgumentList @('artisan', 'migrate', '--force') -Description 'Migrasi database'
    }

    Invoke-CheckedCommand -FilePath 'php' -ArgumentList @('artisan', 'app:data-integrity-audit') -Description 'Audit integritas data operasional'
    Invoke-CheckedCommand -FilePath 'php' -ArgumentList @('artisan', 'schedule:list') -Description 'Validasi scheduler'
    Invoke-CheckedCommand -FilePath 'php' -ArgumentList @('artisan', 'optimize:clear') -Description 'Pembersihan cache akhir'
    Invoke-CheckedCommand -FilePath 'php' -ArgumentList @('artisan', 'optimize') -Description 'Optimasi aplikasi'
} catch {
    try {
        Invoke-CheckedCommand -FilePath 'php' -ArgumentList @('artisan', 'optimize:clear') -Description 'Pembersihan cache setelah kegagalan'
    } catch {
        Write-Warning $_.Exception.Message
    }

    throw
} finally {
    if ($maintenanceEnabled) {
        Invoke-CheckedCommand -FilePath 'php' -ArgumentList @('artisan', 'up') -Description 'Menonaktifkan mode maintenance'
    }
}

if (-not $SkipBenchmark) {
    Invoke-CheckedCommand -FilePath 'php' -ArgumentList @(
        'artisan',
        'app:benchmark-endpoints',
        "--runs=$BenchmarkRuns",
        "--max-average-ms=$MaxAverageMs",
        "--max-query-count=$MaxQueries"
    ) -Description 'Benchmark endpoint utama'
}
