param(
    [Parameter(Mandatory = $true)]
    [string]$Version
)

$ErrorActionPreference = 'Stop'

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$buildDir = Join-Path $root 'build'
$stageDir = Join-Path $buildDir 'asn-core'
$zipPath = Join-Path $buildDir ("asn-core-v{0}.zip" -f $Version)
$mainFile = Join-Path $root 'asn-core.php'

$mainSource = Get-Content -Raw -Path $mainFile
if ($mainSource -notmatch ("Version:\s*" + [regex]::Escape($Version))) {
    throw "Plugin header version does not match requested release version $Version."
}
if ($mainSource -notmatch ("ASN_CORE_VERSION',\s*'" + [regex]::Escape($Version) + "'")) {
    throw "ASN_CORE_VERSION does not match requested release version $Version."
}

if (Test-Path $buildDir) {
    Remove-Item $buildDir -Recurse -Force
}
New-Item -ItemType Directory -Path $stageDir -Force | Out-Null

$runtimeEntries = @(
    'asn-core.php',
    'uninstall.php',
    'includes',
    'modules',
    'integrations',
    'admin',
    'public'
)

foreach ($entry in $runtimeEntries) {
    $source = Join-Path $root $entry
    if (-not (Test-Path $source)) {
        throw "Required runtime path is missing: $entry"
    }

    if ((Get-Item $source).PSIsContainer) {
        Copy-Item $source -Destination $stageDir -Recurse -Force
    } else {
        Copy-Item $source -Destination $stageDir -Force
    }
}

$fileStream = [System.IO.File]::Open($zipPath, [System.IO.FileMode]::Create)
$archive = New-Object System.IO.Compression.ZipArchive(
    $fileStream,
    [System.IO.Compression.ZipArchiveMode]::Create
)

try {
    Get-ChildItem $stageDir -Recurse -File | ForEach-Object {
        $relative = $_.FullName.Substring($stageDir.Length).TrimStart('\', '/')
        $entryName = 'asn-core/' + $relative.Replace('\', '/')
        $entry = $archive.CreateEntry($entryName, [System.IO.Compression.CompressionLevel]::Optimal)
        $entryStream = $entry.Open()
        $inputStream = [System.IO.File]::OpenRead($_.FullName)
        try {
            $inputStream.CopyTo($entryStream)
        } finally {
            $inputStream.Dispose()
            $entryStream.Dispose()
        }
    }
} finally {
    $archive.Dispose()
    $fileStream.Dispose()
}

$excludedPrefixes = @(
    'asn-core/.git/',
    'asn-core/.github/',
    'asn-core/tests/',
    'asn-core/docs/',
    'asn-core/scripts/',
    'asn-core/vendor/',
    'asn-core/build/'
)
$excludedFiles = @(
    'asn-core/composer.json',
    'asn-core/composer.lock',
    'asn-core/phpunit.xml.dist',
    'asn-core/phpcs.xml.dist'
)

$check = [System.IO.Compression.ZipFile]::OpenRead($zipPath)
try {
    foreach ($entry in $check.Entries) {
        if ($entry.FullName.Contains('\')) {
            throw "Invalid Windows-style path in ZIP: $($entry.FullName)"
        }
        if (-not $entry.FullName.StartsWith('asn-core/')) {
            throw "ZIP entry is outside the asn-core root: $($entry.FullName)"
        }
        foreach ($prefix in $excludedPrefixes) {
            if ($entry.FullName.StartsWith($prefix)) {
                throw "Development path leaked into release ZIP: $($entry.FullName)"
            }
        }
        if ($excludedFiles -contains $entry.FullName) {
            throw "Development file leaked into release ZIP: $($entry.FullName)"
        }
    }
} finally {
    $check.Dispose()
}

Write-Host "Release package verified: $zipPath"
