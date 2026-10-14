$ErrorActionPreference = 'Stop'

$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$logDirectory = Join-Path $env:LOCALAPPDATA 'SIGERU'
$logPath = Join-Path $logDirectory 'auto-sync.log'
$pidPath = Join-Path $logDirectory 'auto-sync.pid'
$debounceSeconds = 10
$retrySeconds = 120
$pendingSince = $null
$lastPushAttempt = Get-Date

New-Item -ItemType Directory -Path $logDirectory -Force | Out-Null
Set-Content -LiteralPath $pidPath -Value $PID -Encoding ASCII

function Write-AutoSyncLog {
    param([string]$Message)

    $entry = '{0} {1}' -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'), $Message
    Add-Content -LiteralPath $logPath -Value $entry -Encoding UTF8
}

function Invoke-Git {
    param([string[]]$Arguments)

    $previousPreference = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    try {
        $output = & git -C $projectRoot @Arguments 2>&1
        $exitCode = $LASTEXITCODE
    } finally {
        $ErrorActionPreference = $previousPreference
    }
    if ($output) {
        $output | ForEach-Object { Write-AutoSyncLog ([string]$_) }
    }
    return $exitCode
}

function Sync-Project {
    $stageExit = Invoke-Git @('add', '--all', '--', '.gitignore', 'Backend', 'Frontend', 'scripts')
    if ($stageExit -ne 0) {
        Write-AutoSyncLog "ERROR: git add failed with exit code $stageExit."
        return
    }

    $diffExit = Invoke-Git @('diff', '--cached', '--quiet')
    if ($diffExit -eq 1) {
        $message = 'Auto-sync: {0}' -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss')
        $commitExit = Invoke-Git @('commit', '-m', $message)
        if ($commitExit -ne 0) {
            Write-AutoSyncLog "ERROR: git commit failed with exit code $commitExit."
            return
        }
        Write-AutoSyncLog 'Committed project changes.'
    } elseif ($diffExit -ne 0) {
        Write-AutoSyncLog "ERROR: git diff failed with exit code $diffExit."
        return
    }

    $script:lastPushAttempt = Get-Date
    $pushExit = Invoke-Git @('push', 'origin', 'main')
    if ($pushExit -eq 0) {
        Write-AutoSyncLog 'GitHub sync completed.'
    } else {
        Write-AutoSyncLog "ERROR: git push failed with exit code $pushExit. Will retry."
    }
}

$watcher = New-Object System.IO.FileSystemWatcher
$watcher.Path = $projectRoot
$watcher.Filter = '*'
$watcher.IncludeSubdirectories = $true
$watcher.NotifyFilter = [System.IO.NotifyFilters]'FileName, DirectoryName, LastWrite, Size'
$watcher.InternalBufferSize = 32768
$watcher.EnableRaisingEvents = $true

Write-AutoSyncLog "Started watching $projectRoot."

try {
    while ($true) {
        $change = $watcher.WaitForChanged([System.IO.WatcherChangeTypes]::All, 1000)
        if (-not $change.TimedOut -and $change.Name -notmatch '(^|[\\/])\.git([\\/]|$)') {
            $pendingSince = Get-Date
        }

        if ($null -ne $pendingSince -and ((Get-Date) - $pendingSince).TotalSeconds -ge $debounceSeconds) {
            $pendingSince = $null
            Sync-Project
        } elseif (((Get-Date) - $lastPushAttempt).TotalSeconds -ge $retrySeconds) {
            $previousPreference = $ErrorActionPreference
            $ErrorActionPreference = 'Continue'
            try {
                $ahead = & git -C $projectRoot rev-list --count origin/main..HEAD 2>&1
                $aheadExit = $LASTEXITCODE
            } finally {
                $ErrorActionPreference = $previousPreference
            }
            if ($aheadExit -eq 0) {
                if ([int]$ahead -gt 0) {
                    Sync-Project
                }
            } else {
                $ahead | ForEach-Object { Write-AutoSyncLog ([string]$_) }
                Write-AutoSyncLog "ERROR: could not check unpushed commits (exit code $aheadExit)."
            }
            $lastPushAttempt = Get-Date
        }
    }
} catch {
    Write-AutoSyncLog ("FATAL: " + $_.Exception.Message)
    throw
} finally {
    $watcher.EnableRaisingEvents = $false
    $watcher.Dispose()
    if ((Test-Path -LiteralPath $pidPath) -and (Get-Content -LiteralPath $pidPath -Raw).Trim() -eq [string]$PID) {
        Remove-Item -LiteralPath $pidPath -Force
    }
}
