# Copy this file to scripts/spreadsheet-sync.local.ps1 and adjust the values.
# The local file is ignored by Git because it contains machine-specific paths.

$SpreadsheetSyncConfig = @{
    LocalPath = 'C:\Users\Mauricio\OneDrive\Financeiro.xlsx'
    RemoteHost = 'magento@ftp.oficinadodev.com.br'
    RemotePort = 57778
    SshKeyPath = "$HOME\.ssh\codex_dashboard_ed25519"
    RemotePath = '/var/www/dashboard.oficinadodev.com.br/html/storage/private/spreadsheets/financeiro.xlsx'
    StatePath = "$PSScriptRoot\spreadsheet-sync.state.json"
    LogPath = "$PSScriptRoot\spreadsheet-sync.log"
}
