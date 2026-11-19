$ErrorActionPreference = 'Stop'
Set-Location -LiteralPath $PSScriptRoot
$portablePhp = Join-Path $PSScriptRoot '../work/php83/php.exe'
if (Test-Path -LiteralPath $portablePhp) { $phpCommand = (Resolve-Path -LiteralPath $portablePhp).Path }
elseif (Get-Command php -ErrorAction SilentlyContinue) { $phpCommand = (Get-Command php).Source }
else { throw 'PHP tidak ditemukan. Pasang PHP 8.2 atau lebih baru, lalu ikuti README.md.' }
& $phpCommand -r 'if (PHP_VERSION_ID < 80200) { fwrite(STDERR, "Diperlukan PHP 8.2 atau lebih baru.\n"); exit(1); }'
if ($LASTEXITCODE -ne 0) { throw 'Versi PHP tidak memenuhi kebutuhan Laravel.' }
if (-not (Test-Path -LiteralPath 'vendor/autoload.php')) { throw 'Dependensi belum tersedia. Jalankan composer install sesuai README.md.' }
if (-not (Test-Path -LiteralPath '.env')) {
    Copy-Item -LiteralPath '.env.example' -Destination '.env'
    & $phpCommand artisan key:generate
}
Write-Host 'SIAP-Wisata demo: http://127.0.0.1:8017/login'
Write-Host 'Tekan Ctrl+C untuk menghentikan server lokal.'
& $phpCommand artisan serve --host=127.0.0.1 --port=8017
