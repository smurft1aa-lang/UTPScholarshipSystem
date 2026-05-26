param(
    [string]$Source = $PSScriptRoot,
    [string]$Destination = "\\wsl$\Ubuntu\home\azims\utp-scholarship"
)

Write-Host "Syncing files to WSL..." -ForegroundColor Cyan

# Exclude directories that shouldn't be synced
$ExcludeDirs = @(".git", "node_modules", "vendor", ".phpunit.cache", ".phpstan.cache", ".phpcs.cache", "uploads", "coverage", "logs")

# Robocopy options:
# /MIR  : Mirrors a directory tree (equivalent to /E plus /PURGE)
# /FFT  : Assumes FAT file times (2-second granularity) - useful for crossing filesystems
# /Z    : Copies files in restartable mode
# /XA:H : Excludes hidden files
# /W:1  : Wait time between retries
# /R:1  : Number of retries
# /NDL  : No Directory List - don't log directories
# /NJH  : No Job Header
# /NJS  : No Job Summary
robocopy $Source $Destination /MIR /FFT /XA:H /W:1 /R:1 /NDL /NJH /NJS /XD $ExcludeDirs

Write-Host "Sync complete!" -ForegroundColor Green
