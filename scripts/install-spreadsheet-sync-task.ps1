param(
    [string] $TaskName = 'Dashboard Spreadsheet Sync',
    [string] $ScriptPath = "$PSScriptRoot\sync-spreadsheet.ps1",
    [string] $ConfigPath = "$PSScriptRoot\spreadsheet-sync.local.ps1",
    [int] $IntervalMinutes = 30
)

$ErrorActionPreference = 'Stop'

if (-not (Test-Path -LiteralPath $ScriptPath)) {
    throw "Script não encontrado: $ScriptPath"
}

if (-not (Test-Path -LiteralPath $ConfigPath)) {
    throw "Configuração local não encontrada: $ConfigPath"
}

$taskCommand = "powershell.exe -NoProfile -ExecutionPolicy Bypass -File `"$ScriptPath`" -ConfigPath `"$ConfigPath`""

try {
    $action = New-ScheduledTaskAction -Execute 'powershell.exe' -Argument "-NoProfile -ExecutionPolicy Bypass -File `"$ScriptPath`" -ConfigPath `"$ConfigPath`""
    $triggerAtLogon = New-ScheduledTaskTrigger -AtLogOn
    $triggerRecurring = New-ScheduledTaskTrigger -Once -At (Get-Date).AddMinutes(1) `
        -RepetitionInterval (New-TimeSpan -Minutes $IntervalMinutes) `
        -RepetitionDuration (New-TimeSpan -Days 3650)

    $settings = New-ScheduledTaskSettingsSet `
        -AllowStartIfOnBatteries `
        -DontStopIfGoingOnBatteries `
        -StartWhenAvailable `
        -MultipleInstances IgnoreNew

    Register-ScheduledTask `
        -TaskName $TaskName `
        -Action $action `
        -Trigger @($triggerAtLogon, $triggerRecurring) `
        -Settings $settings `
        -Description 'Sincroniza a planilha financeira do OneDrive local com o servidor do dashboard.' `
        -Force | Out-Null
} catch {
    Write-Warning "Register-ScheduledTask falhou. Tentando fallback com schtasks.exe: $($_.Exception.Message)"
    & schtasks.exe /Create /F /TN $TaskName /SC MINUTE /MO $IntervalMinutes /TR $taskCommand | Out-Host

    if ($LASTEXITCODE -ne 0) {
        throw "Não foi possível criar a tarefa pelo schtasks.exe. Código: $LASTEXITCODE"
    }
}

Write-Host "Tarefa '$TaskName' instalada/atualizada com intervalo de $IntervalMinutes minutos."
Write-Host "Para testar agora: powershell -NoProfile -ExecutionPolicy Bypass -File `"$ScriptPath`" -ConfigPath `"$ConfigPath`" -Force"
