$ErrorActionPreference = 'Stop'
$repoRoot = (Resolve-Path (Join-Path $PSScriptRoot '../..')).Path
$composePath = Join-Path $PSScriptRoot 'compose.yaml'
$archivePath = Join-Path ([System.IO.Path]::GetTempPath()) ('glpi-history-' + [guid]::NewGuid() + '.tar')
$listPath = $archivePath + '.txt'

try {
    # Include working changes and new files, but never local databases, config or dependencies.
    $paths = git -C $repoRoot -c core.quotepath=false ls-files --cached --others --exclude-standard
    if ($LASTEXITCODE -ne 0) { throw 'Cannot list repository files' }
    [System.IO.File]::WriteAllLines($listPath, $paths, [System.Text.UTF8Encoding]::new($false))
    tar -cf $archivePath -C $repoRoot -T $listPath
    if ($LASTEXITCODE -ne 0) { throw 'Cannot archive working tree' }
    docker compose -f $composePath cp $archivePath app:/tmp/history-source.tar
    if ($LASTEXITCODE -ne 0) { throw 'Cannot copy source to Docker' }
    docker compose -f $composePath exec -T --user root app sh -lc 'tar -xf /tmp/history-source.tar -C /var/www/glpi && find tools .github .docker -name "*.sh" -exec sed -i "s/\r$//" {} + && chown -R www-data:www-data /var/www/glpi && rm /tmp/history-source.tar'
    if ($LASTEXITCODE -ne 0) { throw 'Cannot extract source in Docker' }
} finally {
    Remove-Item -LiteralPath $archivePath, $listPath -Force -ErrorAction SilentlyContinue
}
