#!/usr/bin/env pwsh
# BOSTARTER Frontend Cleanup Validation Script

Write-Host "🚀 BOSTARTER Frontend Cleanup Validation" -ForegroundColor Green
Write-Host "=" * 50

$ErrorCount = 0
$SuccessCount = 0

function Test-Item {
    param($Name, $Condition, $Details = "")
    if ($Condition) {
        Write-Host "✅ $Name" -ForegroundColor Green
        if ($Details) { Write-Host "   $Details" -ForegroundColor Gray }
        $script:SuccessCount++
    } else {
        Write-Host "❌ $Name" -ForegroundColor Red
        if ($Details) { Write-Host "   $Details" -ForegroundColor Yellow }
        $script:ErrorCount++
    }
}

# Test 1: Core Files Existence
Write-Host "`n📁 Testing Core Files..." -ForegroundColor Cyan

$CoreFiles = @(
    "c:\xampp\htdocs\BOSTARTER\frontend\sw.js",
    "c:\xampp\htdocs\BOSTARTER\frontend\index.php",
    "c:\xampp\htdocs\BOSTARTER\frontend\offline.html",
    "c:\xampp\htdocs\BOSTARTER\frontend\verification.html",
    "c:\xampp\htdocs\BOSTARTER\frontend\test-performance.html"
)

foreach ($file in $CoreFiles) {
    $exists = Test-Path $file
    $name = Split-Path $file -Leaf
    Test-Item "Core file: $name" $exists
}

# Test 2: JavaScript Modules
Write-Host "`n⚡ Testing JavaScript Modules..." -ForegroundColor Cyan

$JSModules = @(
    "accessibility-manager.js",
    "animation-manager.js", 
    "error-manager.js",
    "performance-auditor.js",
    "ui-components.js",
    "optimized-image-loader.js",
    "resource-optimizer.js",
    "mobile-optimizer.js",
    "optimization-validator.js",
    "sw-register.js"
)

foreach ($module in $JSModules) {
    $path = "c:\xampp\htdocs\BOSTARTER\frontend\js\$module"
    $exists = Test-Path $path
    
    if ($exists) {
        $size = (Get-Item $path).Length
        Test-Item "JS Module: $module" $true "Size: $size bytes"
    } else {
        Test-Item "JS Module: $module" $false "File not found"
    }
}

# Test 3: CSS Files
Write-Host "`n🎨 Testing CSS Files..." -ForegroundColor Cyan

$CSSFiles = @(
    "unified-styles.css",
    "enhanced-accessibility.css"
)

foreach ($css in $CSSFiles) {
    $path = "c:\xampp\htdocs\BOSTARTER\frontend\css\$css"
    $exists = Test-Path $path
    
    if ($exists) {
        $size = (Get-Item $path).Length
        Test-Item "CSS File: $css" $true "Size: $size bytes"
    } else {
        Test-Item "CSS File: $css" $false "File not found"
    }
}

# Test 4: Server Status
Write-Host "`n🌐 Testing Server Status..." -ForegroundColor Cyan

try {
    $serverTest = Test-NetConnection -ComputerName localhost -Port 8080 -WarningAction SilentlyContinue
    Test-Item "Development server on port 8080" $serverTest.TcpTestSucceeded
} catch {
    Test-Item "Development server on port 8080" $false "Connection failed"
}

# Test 5: Documentation Files
Write-Host "`n📚 Testing Documentation..." -ForegroundColor Cyan

$DocFiles = @(
    "OPTIMIZATION_GUIDE.md",
    "CLEANUP_SUMMARY.md"
)

foreach ($doc in $DocFiles) {
    $path = "c:\xampp\htdocs\BOSTARTER\frontend\$doc"
    $exists = Test-Path $path
    Test-Item "Documentation: $doc" $exists
}

# Test 6: File Integrity Check
Write-Host "`n🔍 Testing File Integrity..." -ForegroundColor Cyan

# Check service worker version
$swContent = Get-Content "c:\xampp\htdocs\BOSTARTER\frontend\sw.js" -Raw
$hasVersion = $swContent -match "VERSION = '3\.0\.0'"
Test-Item "Service Worker version 3.0.0" $hasVersion

# Check if key functions exist in service worker
$hasCacheStrategy = $swContent -match "cacheFirstStrategy"
Test-Item "Service Worker cache strategies" $hasCacheStrategy

$hasEventListeners = $swContent -match "addEventListener"
Test-Item "Service Worker event listeners" $hasEventListeners

# Summary
Write-Host "`n" + "=" * 50
Write-Host "📊 VALIDATION SUMMARY" -ForegroundColor Yellow
Write-Host "=" * 50

$TotalTests = $SuccessCount + $ErrorCount
$SuccessRate = if ($TotalTests -gt 0) { [Math]::Round(($SuccessCount / $TotalTests) * 100, 1) } else { 0 }

Write-Host "✅ Passed: $SuccessCount tests" -ForegroundColor Green
Write-Host "❌ Failed: $ErrorCount tests" -ForegroundColor Red
Write-Host "📈 Success Rate: $SuccessRate%" -ForegroundColor Yellow

if ($ErrorCount -eq 0) {
    Write-Host "`n🎉 ALL TESTS PASSED! BOSTARTER frontend is fully optimized and ready!" -ForegroundColor Green
    Write-Host "🌐 Access verification dashboard: http://localhost:8080/verification.html" -ForegroundColor Cyan
    Write-Host "🏠 Access main application: http://localhost:8080/index.php" -ForegroundColor Cyan
} else {
    Write-Host "`n⚠️  Some tests failed. Please review the issues above." -ForegroundColor Yellow
}

Write-Host "`n🔧 Next Steps:" -ForegroundColor Blue
Write-Host "1. Open verification dashboard to run comprehensive tests"
Write-Host "2. Test performance with the performance test page"
Write-Host "3. Verify service worker registration in browser DevTools"
Write-Host "4. Check mobile responsiveness on different devices"

exit $ErrorCount
