param(
    [string]$TaskName = 'AntrianMPP-LaravelScheduler',
    [string]$PhpPath,
    [string]$ProjectPath
)

$ErrorActionPreference = 'Stop'

if (-not $ProjectPath) {
    $ProjectPath = Split-Path -Parent $PSScriptRoot
}

$ProjectPath = (Resolve-Path -LiteralPath $ProjectPath).Path

if (-not $PhpPath) {
    $PhpPath = (Get-Command php -ErrorAction Stop).Source
}

$PhpPath = (Resolve-Path -LiteralPath $PhpPath).Path
$artisanPath = Join-Path $ProjectPath 'artisan'

if (-not (Test-Path -LiteralPath $artisanPath -PathType Leaf)) {
    throw "artisan tidak ditemukan pada $ProjectPath"
}

$currentIdentity = [Security.Principal.WindowsIdentity]::GetCurrent()
$principal = [Security.Principal.WindowsPrincipal]::new($currentIdentity)
$isAdministrator = $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)

if (-not $isAdministrator) {
    throw 'Jalankan PowerShell sebagai Administrator untuk memasang Scheduled Task.'
}

$action = New-ScheduledTaskAction `
    -Execute $PhpPath `
    -Argument 'artisan schedule:run' `
    -WorkingDirectory $ProjectPath

$trigger = New-ScheduledTaskTrigger `
    -Once `
    -At (Get-Date).AddMinutes(1) `
    -RepetitionInterval (New-TimeSpan -Minutes 1)

$settings = New-ScheduledTaskSettingsSet `
    -StartWhenAvailable `
    -DontStopIfGoingOnBatteries `
    -AllowStartIfOnBatteries

Register-ScheduledTask `
    -TaskName $TaskName `
    -Action $action `
    -Trigger $trigger `
    -Settings $settings `
    -User 'SYSTEM' `
    -RunLevel Highest `
    -Force | Out-Null

Write-Host "Scheduled Task '$TaskName' berhasil dipasang."
Write-Host "PHP: $PhpPath"
Write-Host "Project: $ProjectPath"
Write-Host "Verifikasi: Get-ScheduledTask -TaskName '$TaskName'"
