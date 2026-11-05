$ErrorActionPreference = 'Stop'
$raiz = Split-Path $PSScriptRoot -Parent
Set-Location $raiz

$credPath = Join-Path $PSScriptRoot '.ftp-cred'
if (-not (Test-Path $credPath)) { throw 'Falta deploy/.ftp-cred' }
$cred = @{}
Get-Content $credPath | ForEach-Object {
  if ($_ -match '^\s*#' -or $_ -match '^\s*$') { return }
  $p = $_.Split('=', 2)
  if ($p.Count -eq 2) { $cred[$p[0].Trim()] = $p[1].Trim() }
}
$user = $cred['FTP_USER']
$pass = $cred['FTP_PASS']
$hostFtp = $cred['FTP_HOST']
$siteIp = $cred['SITE_IP']
$auth = "${user}:${pass}"

function Ftp-Upload($local, $remote) {
  Write-Host "UP $remote"
  & curl.exe -sS --connect-timeout 60 --retry 2 --ftp-create-dirs --user $auth -T $local "ftp://${hostFtp}/$remote"
  if ($LASTEXITCODE -ne 0) { throw "Falló $remote code $LASTEXITCODE" }
}

$root = (Resolve-Path 'dist-web').Path
$files = Get-ChildItem 'dist-web' -Recurse -File
Write-Host ("dist-web: {0}" -f $files.Count)
foreach ($f in $files) {
  $rel = $f.FullName.Substring($root.Length + 1).Replace('\', '/')
  Ftp-Upload $f.FullName "dist/$rel"
}

$php = @(
  'config/Correo.php',
  'models/Aviso.php',
  'controllers/AvisosController.php',
  'controllers/CitasController.php',
  'api/rutas.php',
  'scripts/enviar-avisos.php',
  'database/migrations/016_indice_aviso_envio.sql',
  'public/migrar_016_once.php'
)
foreach ($p in $php) {
  Ftp-Upload $p ($p.Replace('\', '/'))
}
Ftp-Upload 'config/.env.produccion' 'config/.env'
Write-Host 'OK upload'

Write-Host '--- migrar 016 ---'
$resp = curl.exe -sS --resolve "3bigotesbarberie.com:443:${siteIp}" "https://3bigotesbarberie.com/public/migrar_016_once.php"
Write-Host $resp
if ($resp -notmatch '"ok"\s*:\s*true') { throw "Migración falló: $resp" }

Write-Host '--- borrar migrador ---'
& curl.exe -sS --user $auth -Q "DELE public/migrar_016_once.php" "ftp://${hostFtp}/" | Out-Null
Remove-Item 'public\migrar_016_once.php' -Force -ErrorAction SilentlyContinue

Write-Host '--- salud ---'
curl.exe -sS --resolve "3bigotesbarberie.com:443:${siteIp}" "https://3bigotesbarberie.com/public/api/salud"
Write-Host ''

Write-Host '--- avisos cron dry ---'
$cron = curl.exe -sS --resolve "3bigotesbarberie.com:443:${siteIp}" "https://3bigotesbarberie.com/public/api/avisos/enviar?clave=F5CBTarYYiEHjktXR0JNnpuF9WisQ8v"
Write-Host $cron

Write-Host '--- html assets ---'
$html = curl.exe -sS --resolve "3bigotesbarberie.com:443:${siteIp}" "https://3bigotesbarberie.com/"
[regex]::Matches($html, 'assets/(?:index|PaginaReserva)-[^"]+') | ForEach-Object { $_.Value }
Write-Host 'DONE'
