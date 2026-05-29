# PowerShell script to fix relative links and routing bugs in the Free Fire Tournament Platform

$files = Get-ChildItem -Recurse -Filter "*.php"

foreach ($file in $files) {
    # Skip script files to avoid modifying this script or utility files
    if ($file.Name -eq "fix_routing.ps1" -or $file.FullName -like "*\scripts\scripts\*") {
        continue
    }

    $content = Get-Content $file.FullName -Raw
    $modified = $false

    # 1. Fix Location: ../src/login.php -> Location: ../../src/login.php
    if ($content -match "Location: \.\./src/login\.php") {
        $content = $content -replace "Location: \.\./src/login\.php", "Location: ../../src/login.php"
        $modified = $true
    }

    # 2. Fix Location: ../player/player_dashboard.php -> Location: player_dashboard.php
    if ($content -match "Location: \.\./player/player_dashboard\.php") {
        $content = $content -replace "Location: \.\./player/player_dashboard\.php", "Location: player_dashboard.php"
        $modified = $true
    }

    # 3. Fix ../src/logout.php -> ../../src/logout.php
    if ($content -match "\.\./src/logout\.php") {
        $content = $content -replace "\.\./src/logout\.php", "../../src/logout.php"
        $modified = $true
    }

    # 4. Fix ../src/change_password.php -> ../../src/change_password.php
    if ($content -match "\.\./src/change_password\.php") {
        $content = $content -replace "\.\./src/change_password\.php", "../../src/change_password.php"
        $modified = $true
    }

    # 5. Fix ../src/index.php -> ../../src/index.php
    if ($content -match "\.\./src/index\.php") {
        $content = $content -replace "\.\./src/index\.php", "../../src/index.php"
        $modified = $true
    }

    # 6. Fix ../src/join_tournament.php -> join_tournament.php
    if ($content -match "\.\./src/join_tournament\.php") {
        $content = $content -replace "\.\./src/join_tournament\.php", "join_tournament.php"
        $modified = $true
    }

    # 7. Fix ../src/tournaments.php -> player_dashboard.php
    if ($content -match "\.\./src/tournaments\.php") {
        $content = $content -replace "\.\./src/tournaments\.php", "player_dashboard.php"
        $modified = $true
    }

    # 8. Fix relative assets path for 2-level-deep folders
    if ($file.FullName -match "(admin\\admin\\|creator\\creator\\|player\\player\\)" -and $content -match "\.\./assets/") {
        $content = $content -replace "\.\./assets/", "../../assets/"
        $modified = $true
    }

    # 9. Replace missing PNG logo file references with the existing SVG logo
    if ($content -match "SKYNOXX -- FF\.png") {
        $content = $content -replace "SKYNOXX -- FF\.png", "logo.svg"
        $modified = $true
    }
    if ($content -match "ff-white\.png") {
        $content = $content -replace "ff-white\.png", "logo.svg"
        $modified = $true
    }

    if ($modified) {
        Set-Content $file.FullName -Value $content -NoNewline
        Write-Output "Successfully fixed routing in: $($file.FullName)"
    }
}
