Set shell = CreateObject("WScript.Shell")
Set filesystem = CreateObject("Scripting.FileSystemObject")

scriptDir = filesystem.GetParentFolderName(WScript.ScriptFullName)
syncScript = filesystem.BuildPath(scriptDir, "sync-spreadsheet.ps1")
configPath = filesystem.BuildPath(scriptDir, "spreadsheet-sync.local.ps1")

command = "powershell.exe -NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File """ & syncScript & """ -ConfigPath """ & configPath & """"
shell.Run command, 0, False
