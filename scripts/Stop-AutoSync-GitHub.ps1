$ErrorActionPreference = 'Stop'

$startupShortcut = Join-Path ([Environment]::GetFolderPath('Startup')) 'SIGERU GitHub Sync.lnk'
$pidPath = Join-Path $env:LOCALAPPDATA 'SIGERU\auto-sync.pid'

if (Test-Path -LiteralPath $pidPath) {
    $watcherPid = 0
    if ([int]::TryParse((Get-Content -LiteralPath $pidPath -Raw).Trim(), [ref]$watcherPid)) {
        $process = Get-CimInstance Win32_Process -Filter "ProcessId = $watcherPid"
        if ($null -ne $process -and $process.CommandLine -match 'AutoSync-GitHub\.ps1') {
            Stop-Process -Id $watcherPid
        }
    }
    Remove-Item -LiteralPath $pidPath -Force -ErrorAction SilentlyContinue
}

if (Test-Path -LiteralPath $startupShortcut) {
    Remove-Item -LiteralPath $startupShortcut -Force
}
