$ErrorActionPreference = "Stop"
$root = "d:\My Projects\pglife\img\properties"
$stage = Join-Path $env:TEMP "pglife-photos"
if (Test-Path $stage) { Remove-Item $stage -Recurse -Force }
New-Item -ItemType Directory -Force -Path $stage | Out-Null
# Three photos per listing: bedroom, shared living or kitchen, bath or building.
$photos = @(
    "photo-1522771739844-6a9f6d5f14af","photo-1493809842364-78817add7ffb","photo-1552321554-5fefe8c9ef14",
    "photo-1505693416388-ac5ce068fe85","photo-1560448204-e02f11c3d0e2","photo-1584622650111-993a426fbf0a",
    "photo-1616594039964-ae9021a400a0","photo-1502672260266-1c1ef2d93688","photo-1620626011761-996317b8d101",
    "photo-1617325247661-675ab4b64ae2","photo-1484101403633-562f891dc89a","photo-1584622781564-1d987f7333c1",
    "photo-1595526114035-0d45ed16cfbf","photo-1513694203232-719a280e022f","photo-1507652313519-d4e9174996dd",
    "photo-1540518614846-7eded433c457","photo-1493663284031-b7e3aefcae8e","photo-1560185127-6ed189bf02f4",
    "photo-1631049307264-da0ec9d70304","photo-1522708323590-d24dbb6b0267","photo-1600566753086-00f18fb6b3ea",
    "photo-1611892440504-42a792e24d32","photo-1554995207-c18c203602cb","photo-1556912173-46c336c7fd55",
    "photo-1598928506311-c55ded91a20c","photo-1586023492125-27b2c045efd7","photo-1556909114-f6e7ad7d3136",
    "photo-1578683010236-d716f9a3f461","photo-1560448075-bb485b067938","photo-1600585154340-be6161a56a0c",
    "photo-1566665797739-1674de7a421a","photo-1618221195710-dd6b41faaea6","photo-1501183638710-841dd1904471",
    "photo-1590490360182-c33d57733427","photo-1616486338812-3dadae4b4ace","photo-1600573472592-401b489a3cdc",
    "photo-1618773928121-c32242e63f39","photo-1600210492486-724fe5c67fb0","photo-1600607687939-ce8a6c25118c",
    "photo-1596394516093-501ba68a0ba6","photo-1600566752355-35792bedcfea","photo-1566073771259-6a8506099945",
    "photo-1571896349842-33c89424de2d","photo-1600210492493-0946911123ea","photo-1551882547-ff40c63fe5fa",
    "photo-1631049552057-403cdb8f0658","photo-1615874959474-d609969a20ed","photo-1542314831-068cd1dbfeeb",
    "photo-1582719478250-c89cae4dc85b","photo-1615873968403-89e068629265","photo-1445019980597-93fa8acb246c",
    "photo-1590490359683-658d3d23f972","photo-1600121848594-d8644e57abab","photo-1600585154526-990dced4db0d",
    "photo-1560185007-cde436f6a4d0","photo-1556020685-ae41abfc9365","photo-1600596542815-ffad4c1539a9",
    "photo-1536376072261-38c75010e6c9","photo-1617104678098-de229db51175","photo-1600047509358-9dc75507daeb",
    "photo-1617103996702-96ff29b1c467","photo-1600607687920-4e2a09cf159d","photo-1600566753190-17f0baa2a6c3",
    "photo-1598928636135-d146006ff4be","photo-1616137466211-f939a420be84","photo-1484154218962-a197022b5858",
    "photo-1631889993959-41b4e9c6e3c5","photo-1616628188859-7a11abb6fcc9","photo-1600585152220-90363fe7e115",
    "photo-1571508601891-ca5e7a713859","photo-1604014237800-1c9102c219da","photo-1556909172-54557c7e4fb7",
    "photo-1615876234886-fd9a39fda97f","photo-1616486029423-aaa4789e8c9a","photo-1600573472550-8090b5e0745e"
)
if ($photos.Count -ne 75) { throw "Expected 75 photos, got $($photos.Count)" }
1..25 | ForEach-Object {
    New-Item -ItemType Directory -Force -Path (Join-Path $stage $_) | Out-Null
}
$fail = @()
for ($n = 0; $n -lt $photos.Count; $n++) {
    $prop = [math]::Floor($n / 3) + 1
    $slot = ($n % 3) + 1
    $dest = Join-Path $stage "$prop\$slot.jpg"
    $url = "https://images.unsplash.com/$($photos[$n])?auto=format&fit=crop&w=1400&q=80"
    & curl.exe -fsSL --retry 2 --retry-delay 1 -o $dest $url
    if ($LASTEXITCODE -ne 0 -or -not (Test-Path $dest) -or (Get-Item $dest).Length -lt 20000) {
        $fail += "$prop/$slot $($photos[$n])"
        if (Test-Path $dest) { Remove-Item $dest -Force }
    } else {
        Write-Output "ok $prop/$slot $((Get-Item $dest).Length)"
    }
}
if ($fail.Count) {
    Write-Output "FAILED"
    $fail
    exit 1
}
1..25 | ForEach-Object {
    $dir = Join-Path $root $_
    New-Item -ItemType Directory -Force -Path $dir | Out-Null
    Get-ChildItem $dir -File | Remove-Item -Force
    Copy-Item (Join-Path $stage "$_\*") $dir -Force
}
Write-Output "installed"
