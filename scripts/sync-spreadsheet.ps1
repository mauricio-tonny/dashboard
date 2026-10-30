param(
    [string] $ConfigPath = "$PSScriptRoot\spreadsheet-sync.local.ps1",
    [switch] $Force
)

$ErrorActionPreference = 'Stop'

function Write-SyncLog {
    param(
        [string] $Message,
        [string] $Level = 'INFO'
    )

    $timestamp = Get-Date -Format 'yyyy-MM-dd HH:mm:ss'
    $line = "[$timestamp] [$Level] $Message"
    Write-Host $line

    if ($script:Config -and $script:Config.LogPath) {
        $logDir = Split-Path -Parent $script:Config.LogPath
        if ($logDir -and -not (Test-Path -LiteralPath $logDir)) {
            New-Item -ItemType Directory -Path $logDir -Force | Out-Null
        }

        Add-Content -LiteralPath $script:Config.LogPath -Value $line -Encoding UTF8
    }
}

function Get-FileSha256 {
    param([string] $Path)

    return (Get-FileHash -LiteralPath $Path -Algorithm SHA256).Hash.ToLowerInvariant()
}

function Test-LocalFileUnlocked {
    param([string] $Path)

    try {
        $stream = [System.IO.File]::Open($Path, [System.IO.FileMode]::Open, [System.IO.FileAccess]::ReadWrite, [System.IO.FileShare]::None)
        $stream.Dispose()
        return $true
    } catch {
        return $false
    }
}

function Read-SyncState {
    param([string] $Path)

    if (-not $Path -or -not (Test-Path -LiteralPath $Path)) {
        return @{}
    }

    try {
        $json = Get-Content -LiteralPath $Path -Raw | ConvertFrom-Json
        $state = @{}

        foreach ($property in $json.PSObject.Properties) {
            $state[$property.Name] = $property.Value
        }

        return $state
    } catch {
        return @{}
    }
}

function Save-SyncState {
    param(
        [string] $Path,
        [hashtable] $State
    )

    if (-not $Path) {
        return
    }

    $stateDir = Split-Path -Parent $Path
    if ($stateDir -and -not (Test-Path -LiteralPath $stateDir)) {
        New-Item -ItemType Directory -Path $stateDir -Force | Out-Null
    }

    $State | ConvertTo-Json -Depth 4 | Set-Content -LiteralPath $Path -Encoding UTF8
}

function Send-SyncCallback {
    param(
        [string] $Status,
        [string] $Message,
        [string] $FileHash = '',
        [Nullable[Int64]] $FileSize = $null
    )

    if (-not $script:Config -or -not $script:Config.ContainsKey('DashboardUrl') -or -not $script:Config.ContainsKey('CallbackToken')) {
        return
    }

    if ([string]::IsNullOrWhiteSpace([string] $script:Config.DashboardUrl) -or [string]::IsNullOrWhiteSpace([string] $script:Config.CallbackToken)) {
        return
    }

    $baseUrl = ([string] $script:Config.DashboardUrl).TrimEnd('/')
    $url = "$baseUrl/system/sync/windows-callback"
    $body = @{
        status = $Status
        message = $Message
        file_hash = $FileHash
        occurred_at = (Get-Date).ToString('o')
        machine_name = $env:COMPUTERNAME
        local_path = [string] $script:Config.LocalPath
        remote_path = [string] $script:Config.RemotePath
    }

    if ($null -ne $FileSize) {
        $body.file_size = [string] $FileSize
    }

    try {
        Invoke-RestMethod -Method Post -Uri $url -Headers @{ 'X-Sync-Token' = [string] $script:Config.CallbackToken } -Body $body -TimeoutSec 20 | Out-Null
    } catch {
        Write-SyncLog "Não foi possível registrar callback no dashboard: $($_.Exception.Message)" 'WARN'
    }
}

if (-not (Test-Path -LiteralPath $ConfigPath)) {
    throw "Arquivo de configuração não encontrado: $ConfigPath. Copie scripts\spreadsheet-sync.example.ps1 para scripts\spreadsheet-sync.local.ps1 e ajuste os caminhos."
}

. $ConfigPath

if (-not $SpreadsheetSyncConfig) {
    throw 'A configuração $SpreadsheetSyncConfig não foi encontrada no arquivo informado.'
}

$script:Config = $SpreadsheetSyncConfig

trap {
    Send-SyncCallback -Status 'failed' -Message $_.Exception.Message -FileHash $localHash -FileSize $localFileSize
    break
}

$syncStartHour = if ($script:Config.ContainsKey('SyncStartHour')) { [int] $script:Config.SyncStartHour } else { 8 }
$syncEndHour = if ($script:Config.ContainsKey('SyncEndHour')) { [int] $script:Config.SyncEndHour } else { 18 }
$now = Get-Date
$isWeekday = $now.DayOfWeek -notin @([DayOfWeek]::Saturday, [DayOfWeek]::Sunday)
$isInsideWindow = $isWeekday -and $now.Hour -ge $syncStartHour -and $now.Hour -lt $syncEndHour

if (-not $Force -and -not $isInsideWindow) {
    $message = "Sincronização ignorada fora da janela permitida: segunda a sexta, das $syncStartHour:00 às $syncEndHour:00."
    Write-SyncLog $message
    Send-SyncCallback -Status 'skipped' -Message $message
    exit 0
}

foreach ($requiredKey in @('LocalPath', 'RemoteHost', 'RemotePort', 'SshKeyPath', 'RemotePath', 'StatePath', 'LogPath')) {
    if (-not $script:Config.ContainsKey($requiredKey) -or [string]::IsNullOrWhiteSpace([string] $script:Config[$requiredKey])) {
        throw "Configuração obrigatória ausente: $requiredKey"
    }
}

if (-not (Test-Path -LiteralPath $script:Config.LocalPath)) {
    throw "Planilha local não encontrada: $($script:Config.LocalPath)"
}

if (-not (Test-LocalFileUnlocked -Path $script:Config.LocalPath)) {
    $message = 'Sincronização pausada: a planilha está aberta ou sendo usada pelo OneDrive. Feche o arquivo e tente novamente.'
    Write-SyncLog $message 'WARN'
    Send-SyncCallback -Status 'locked' -Message $message
    exit 0
}

if (-not (Test-Path -LiteralPath $script:Config.SshKeyPath)) {
    throw "Chave SSH não encontrada: $($script:Config.SshKeyPath)"
}

$localHash = Get-FileSha256 -Path $script:Config.LocalPath
$localFileSize = (Get-Item -LiteralPath $script:Config.LocalPath).Length
$state = Read-SyncState -Path $script:Config.StatePath

$remotePath = [string] $script:Config.RemotePath
$remoteDir = $remotePath.Substring(0, $remotePath.LastIndexOf('/'))
$remoteTempPath = "$remotePath.uploading"
$remoteMarkerPath = "$remotePath.server-sync.json"
$ssh = "$env:WINDIR\System32\OpenSSH\ssh.exe"
$scp = "$env:WINDIR\System32\OpenSSH\scp.exe"

if (-not (Test-Path -LiteralPath $ssh)) {
    throw "ssh.exe não encontrado em $ssh"
}

if (-not (Test-Path -LiteralPath $scp)) {
    throw "scp.exe não encontrado em $scp"
}

$remoteMarkerJson = (& $ssh -i $script:Config.SshKeyPath -p $script:Config.RemotePort $script:Config.RemoteHost "if [ -f '$remoteMarkerPath' ]; then cat '$remoteMarkerPath'; fi" 2>$null) -join ''
$remoteMarker = $null

if (-not [string]::IsNullOrWhiteSpace($remoteMarkerJson)) {
    try {
        $remoteMarker = $remoteMarkerJson | ConvertFrom-Json
    } catch {
        Write-SyncLog 'Marcador de sincronização do servidor inválido; seguindo com a verificação normal.' 'WARN'
    }
}

$remoteHash = if ($remoteMarker) { [string] $remoteMarker.file_hash } else { '' }

if (-not [string]::IsNullOrWhiteSpace($remoteHash) -and $remoteHash -ne $localHash) {
    $localHasUnsyncedChanges = -not [string]::IsNullOrWhiteSpace([string] $state.last_hash) -and [string] $state.last_hash -ne $localHash

    if ($localHasUnsyncedChanges) {
        $message = 'Conflito detectado: a planilha local também foi alterada depois da última sincronização. Nenhum arquivo foi sobrescrito.'
        Write-SyncLog $message 'WARN'
        Send-SyncCallback -Status 'conflict' -Message $message -FileHash $localHash -FileSize $localFileSize
        exit 0
    }

    $downloadPath = "$($script:Config.LocalPath).server-download"
    Write-SyncLog "Baixando a versão atualizada pelo sistema. Hash: $remoteHash"
    & $scp -i $script:Config.SshKeyPath -P $script:Config.RemotePort "$($script:Config.RemoteHost):$remotePath" $downloadPath

    if ($LASTEXITCODE -ne 0) {
        throw "Falha ao baixar a planilha atualizada pelo servidor. Código: $LASTEXITCODE"
    }

    Move-Item -LiteralPath $downloadPath -Destination $script:Config.LocalPath -Force
    $downloadedHash = Get-FileSha256 -Path $script:Config.LocalPath
    $downloadedFileSize = (Get-Item -LiteralPath $script:Config.LocalPath).Length
    & $ssh -i $script:Config.SshKeyPath -p $script:Config.RemotePort $script:Config.RemoteHost "rm -f '$remoteMarkerPath'" 2>$null
    Save-SyncState -Path $script:Config.StatePath -State @{
        last_hash = $downloadedHash
        last_sync_at = (Get-Date).ToString('o')
        local_path = $script:Config.LocalPath
        remote_path = $script:Config.RemotePath
        last_file_size = $downloadedFileSize
    }

    $message = 'A planilha foi atualizada pelo sistema e baixada para o computador.'
    Write-SyncLog $message
    Send-SyncCallback -Status 'downloaded' -Message $message -FileHash $downloadedHash -FileSize $downloadedFileSize
    exit 0
}

if (-not $Force -and $state.last_hash -eq $localHash) {
    $message = "Planilha sem alterações. Hash: $localHash"
    Write-SyncLog $message
    Send-SyncCallback -Status 'unchanged' -Message $message -FileHash $localHash -FileSize $localFileSize
    exit 0
}

Write-SyncLog "Preparando diretório remoto $remoteDir"
& $ssh -i $script:Config.SshKeyPath -p $script:Config.RemotePort $script:Config.RemoteHost "mkdir -p '$remoteDir'"

if ($LASTEXITCODE -ne 0) {
    throw "Falha ao preparar diretório remoto. Código: $LASTEXITCODE"
}

Write-SyncLog "Enviando planilha atualizada para o servidor. Hash: $localHash"
& $scp -i $script:Config.SshKeyPath -P $script:Config.RemotePort $script:Config.LocalPath "$($script:Config.RemoteHost):$remoteTempPath"

if ($LASTEXITCODE -ne 0) {
    throw "Falha no upload da planilha. Código: $LASTEXITCODE"
}

Write-SyncLog 'Publicando arquivo remoto de forma atômica'
& $ssh -i $script:Config.SshKeyPath -p $script:Config.RemotePort $script:Config.RemoteHost "mv '$remoteTempPath' '$remotePath' && chmod 660 '$remotePath' && rm -f '$remoteMarkerPath'"

if ($LASTEXITCODE -ne 0) {
    throw "Falha ao publicar planilha remota. Código: $LASTEXITCODE"
}

Save-SyncState -Path $script:Config.StatePath -State @{
    last_hash = $localHash
    last_sync_at = (Get-Date).ToString('o')
    local_path = $script:Config.LocalPath
    remote_path = $script:Config.RemotePath
    last_file_size = $localFileSize
}

$successMessage = "Sincronização concluída com sucesso."
Write-SyncLog $successMessage
Send-SyncCallback -Status 'uploaded' -Message $successMessage -FileHash $localHash -FileSize $localFileSize
