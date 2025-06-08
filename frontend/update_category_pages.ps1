# Batch update category pages script
# This script updates the remaining category pages with new script references

$categories = @('fotografia', 'cibo', 'danza', 'artigianato', 'teatro', 'moda', 'giornalismo', 'giochi', 'fumetti')

foreach ($category in $categories) {
    $filePath = "c:\xampp\htdocs\BOSTARTER\frontend\assets\$category\index.html"
    
    if (Test-Path $filePath) {
        Write-Host "Updating $category page..."
        
        # Read the file content
        $content = Get-Content $filePath -Raw
        
        # Replace the old script references (simpler pattern)
        $oldPattern = '<script src="/frontend/assets/shared/js/category-config.js"></script>'
        $newScripts = @"
    <!-- Core utilities -->
    <script src="/frontend/js/utils/common-functions.js"></script>
    
    <!-- Component system -->
    <script src="/frontend/js/components/ui-components.js"></script>
    
    <!-- Category configuration -->
    <script src="/frontend/js/utils/category-config.js"></script>
    
    <!-- Category manager -->
    <script src="/frontend/js/managers/base-category-manager.js"></script>
    <script src="/frontend/js/managers/generic-category-manager.js"></script>
"@
        
        # Replace old script reference
        $content = $content -replace [regex]::Escape($oldPattern), $newScripts
        
        # Replace the initialization function call
        $content = $content -replace "initializeCommonFunctions\('$category'\);", "new GenericCategoryManager('$category');"
        $content = $content -replace "// Inizializza le funzioni comuni per la categoria", "// Initialize generic category manager for $category"
        
        # Write the updated content back
        Set-Content $filePath -Value $content -Encoding UTF8
        
        Write-Host "Updated $category page"
    } else {
        Write-Host "File not found: $filePath"
    }
}

Write-Host "Batch update completed!"
