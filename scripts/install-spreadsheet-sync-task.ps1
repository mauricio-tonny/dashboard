param(
    [string] $TaskName = 'Dashboard Spreadsheet Sync',
    [string] $ScriptPath = "$PSScriptRoot\sync-spreadsheet.ps1",
    [string] $ConfigPath = "$PSScriptRoot\spreadsheet-sync.local.ps1",
    [string] $HiddenRunnerPath = "$PSScriptRoot\run-spreadsheet-sync-hidden.vbs",
    [int] $IntervalMinutes = 30,
    [int] $StartHour = 8,
    [int] $EndHour = 18
)

$ErrorActionPreference = 'Stop'

if (-not (Test-Path -LiteralPath $ScriptPath)) {
    throw "Script não encontrado: $ScriptPath"
}

if (-not (Test-Path -LiteralPath $ConfigPath)) {
    throw "Configuração local não encontrada: $ConfigPath"
}

if (-not (Test-Path -LiteralPath $HiddenRunnerPath)) {
    throw "Executor oculto não encontrado: $HiddenRunnerPath"
}

$taskCommand = "`"$env:WINDIR\System32\wscript.exe`" `"$HiddenRunnerPath`""
$windowMinutes = (($EndHour - $StartHour) * 60) - $IntervalMinutes

if ($windowMinutes -lt $IntervalMinutes) {
    throw 'Janela de sincronização inválida. O horário final precisa permitir ao menos uma execução dentro do intervalo.'
}

& schtasks.exe /Create /F /TN $TaskName /SC WEEKLY /D MON,TUE,WED,THU,FRI /ST "$($StartHour.ToString('00')):00" /RI $IntervalMinutes /DU "$([Math]::Floor($windowMinutes / 60).ToString('00')):$((($windowMinutes % 60)).ToString('00'))" /TR $taskCommand | Out-Host

if ($LASTEXITCODE -ne 0) {
    throw "Não foi possível criar a tarefa pelo schtasks.exe. Código: $LASTEXITCODE"
}

Write-Host "Tarefa '$TaskName' instalada/atualizada com intervalo de $IntervalMinutes minutos, de segunda a sexta, das ${StartHour}:00 às ${EndHour}:00."
Write-Host "Para testar agora: powershell -NoProfile -ExecutionPolicy Bypass -File `"$ScriptPath`" -ConfigPath `"$ConfigPath`" -Force"
