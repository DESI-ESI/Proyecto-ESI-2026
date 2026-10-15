$ErrorActionPreference = 'Stop'

$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$logDirectory = Join-Path $env:LOCALAPPDATA 'SIGERU'
$logPath = Join-Path $logDirectory 'auto-sync.log'
$pidPath = Join-Path $logDirectory 'auto-sync.pid'
$schemaPath = Join-Path $projectRoot 'Backend\database\sigeru_schema.sql'
$schemaRefreshSeconds = 120
$debounceSeconds = 10
$retrySeconds = 120
$pendingSince = $null
$lastPushAttempt = Get-Date
$lastSchemaRefresh = [DateTime]::MinValue

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

function Update-DatabaseSchema {
    $dumpCommand = Join-Path $env:SystemDrive 'xampp\mysql\bin\mysqldump.exe'
    if (-not (Test-Path -LiteralPath $dumpCommand)) {
        throw "MySQL schema exporter was not found at $dumpCommand."
    }

    $previousPreference = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    try {
        $dump = & $dumpCommand '-u' 'root' '--no-data' '--skip-comments' '--skip-add-drop-table' '--default-character-set=utf8mb4' 'sigeru' 2>&1
        $dumpExit = $LASTEXITCODE
    } finally {
        $ErrorActionPreference = $previousPreference
    }
    if ($dumpExit -ne 0) {
        $dump | ForEach-Object { Write-AutoSyncLog ([string]$_) }
        throw "mysqldump failed with exit code $dumpExit."
    }

    $schema = (($dump | ForEach-Object { [string]$_ }) -join "`n")
    $schema = [regex]::Replace($schema, ' AUTO_INCREMENT=\d+', '')
    $schema = $schema.TrimEnd() + "`n"
    if ($schema -notmatch '(?m)^CREATE TABLE ' -or
        $schema -match '(?im)^\s*(INSERT|REPLACE)\s+INTO\b' -or
        $schema -match '(?im)^\s*DROP\s+TABLE\b' -or
        $schema -match 'AUTO_INCREMENT=\d+') {
        throw 'The database export did not pass schema-only safety validation.'
    }

    $currentSchema = if (Test-Path -LiteralPath $schemaPath) {
        [System.IO.File]::ReadAllText($schemaPath, [System.Text.Encoding]::UTF8)
    } else {
        ''
    }
    $changed = $currentSchema -cne $schema
    if ($changed) {
        [System.IO.Directory]::CreateDirectory((Split-Path -Parent $schemaPath)) | Out-Null
        [System.IO.File]::WriteAllText($schemaPath, $schema, (New-Object System.Text.UTF8Encoding($false)))
        Write-AutoSyncLog 'Refreshed the schema-only database export.'
    }
    $script:lastSchemaRefresh = Get-Date
    return $changed
}

function Get-UnpushedCommitCount {
    $previousPreference = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    try {
        $count = & git -C $projectRoot rev-list --count origin/main..HEAD 2>&1
        $exitCode = $LASTEXITCODE
    } finally {
        $ErrorActionPreference = $previousPreference
    }
    if ($exitCode -ne 0) {
        $count | ForEach-Object { Write-AutoSyncLog ([string]$_) }
        Write-AutoSyncLog "ERROR: could not check unpushed commits (exit code $exitCode)."
        return $null
    }
    return [int]$count
}

function Sync-Project {
    $null = Update-DatabaseSchema
    $stageExit = Invoke-Git @(
        'add', '--all', '--', '.gitignore', 'Backend', 'Frontend', 'scripts',
        ':(exclude)Backend/migrations/20261014_add_camion_route.sql',
        ':(exclude)Backend/migrations/20261014_allow_empleado_role.sql',
        ':(exclude)Backend/migrations/20261014_create_notifications.sql'
    )
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
    $initialSchemaChanged = Update-DatabaseSchema
    if ($initialSchemaChanged) {
        $pendingSince = Get-Date
    }

    while ($true) {
        $change = $watcher.WaitForChanged([System.IO.WatcherChangeTypes]::All, 1000)
        if (-not $change.TimedOut -and $change.Name -notmatch '(^|[\\/])\.git([\\/]|$)') {
            if ($change.Name -notmatch '^Backend[\\/]database[\\/]sigeru_schema\.sql$') {
                $pendingSince = Get-Date
            }
        }

        if ($null -ne $pendingSince -and ((Get-Date) - $pendingSince).TotalSeconds -ge $debounceSeconds) {
            $pendingSince = $null
            Sync-Project
        } elseif (((Get-Date) - $lastSchemaRefresh).TotalSeconds -ge $schemaRefreshSeconds) {
            $schemaChanged = Update-DatabaseSchema
            if ($schemaChanged) {
                Sync-Project
            } elseif (((Get-Date) - $lastPushAttempt).TotalSeconds -ge $retrySeconds) {
                $ahead = Get-UnpushedCommitCount
                if ($null -ne $ahead -and $ahead -gt 0) {
                    Sync-Project
                }
                $lastPushAttempt = Get-Date
            }
        } elseif (((Get-Date) - $lastPushAttempt).TotalSeconds -ge $retrySeconds) {
            $ahead = Get-UnpushedCommitCount
            if ($null -ne $ahead -and $ahead -gt 0) {
                Sync-Project
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
