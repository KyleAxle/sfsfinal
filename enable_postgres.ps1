# PowerShell script to help enable PostgreSQL extension in XAMPP

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "PHP PostgreSQL Extension Enabler" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$phpIni = "C:\xampp\php\php.ini"
$extDir = "C:\xampp\php\ext"

# Check if php.ini exists
if (-not (Test-Path $phpIni)) {
    Write-Host "ERROR: php.ini not found at: $phpIni" -ForegroundColor Red
    Write-Host "Please check your XAMPP installation path." -ForegroundColor Yellow
    pause
    exit
}

Write-Host "Found php.ini at: $phpIni" -ForegroundColor Green
Write-Host ""

# Check if extension files exist
$pdoPgSqlDll = Join-Path $extDir "php_pdo_pgsql.dll"
$pgSqlDll = Join-Path $extDir "php_pgsql.dll"

Write-Host "Checking for extension files..." -ForegroundColor Yellow
if (Test-Path $pdoPgSqlDll) {
    Write-Host "✅ Found: php_pdo_pgsql.dll" -ForegroundColor Green
} else {
    Write-Host "❌ Missing: php_pdo_pgsql.dll" -ForegroundColor Red
    Write-Host "   Location: $pdoPgSqlDll" -ForegroundColor Yellow
}

if (Test-Path $pgSqlDll) {
    Write-Host "✅ Found: php_pgsql.dll" -ForegroundColor Green
} else {
    Write-Host "❌ Missing: php_pgsql.dll" -ForegroundColor Red
    Write-Host "   Location: $pgSqlDll" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Current PHP extensions:" -ForegroundColor Yellow
php -m | Select-String -Pattern "pdo|pgsql"

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "MANUAL STEPS REQUIRED:" -ForegroundColor Yellow
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "1. Open php.ini in Notepad (as Administrator):" -ForegroundColor White
Write-Host "   $phpIni" -ForegroundColor Cyan
Write-Host ""
Write-Host "2. Search for and UNCOMMENT (remove ;):" -ForegroundColor White
Write-Host "   extension=pdo_pgsql" -ForegroundColor Green
Write-Host "   extension=pgsql" -ForegroundColor Green
Write-Host ""
Write-Host "3. Save the file" -ForegroundColor White
Write-Host ""
Write-Host "4. Restart Apache in XAMPP Control Panel" -ForegroundColor White
Write-Host ""
Write-Host "5. Run: php test_connection.php" -ForegroundColor White
Write-Host ""

pause


