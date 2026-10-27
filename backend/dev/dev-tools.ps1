# Outils de test local (ASCII uniquement).
# Chargement depuis le dossier backend :  iex (Get-Content dev\dev-tools.ps1 -Raw)

$global:DevBase = 'http://127.0.0.1:8000/api/v1'

function Get-EnvValue([string]$Name) {
  $line = Get-Content .env | Where-Object { $_ -match "^$Name=" } | Select-Object -First 1
  if (-not $line) { return '' }
  return ($line -replace "^$Name=", '').Trim().Trim('"')
}

function Invoke-Api([string]$Method, [string]$Path, [string]$Token = '', $Body = $null) {
  $headers = @{ Accept = 'application/json' }
  if ($Token) { $headers['Authorization'] = "Bearer $Token" }
  $params = @{ Method = $Method; Uri = "$($global:DevBase)$Path"; Headers = $headers }
  if ($null -ne $Body) {
    $params.Body = ($Body | ConvertTo-Json -Depth 5)
    $params.ContentType = 'application/json'
  }
  try {
    Invoke-RestMethod @params -ErrorAction Stop
  } catch {
    $detail = if ($_.ErrorDetails -and $_.ErrorDetails.Message) { $_.ErrorDetails.Message } else { $_.Exception.Message }
    throw "API $Method $Path : $detail"
  }
}

function Get-AdminToken {
  (Invoke-Api POST '/auth/login' '' @{ login = (Get-EnvValue 'ADMIN_EMAIL'); password = (Get-EnvValue 'ADMIN_PASSWORD') }).token
}

function Dev-Seed {
  $t = Get-AdminToken
  $existing = (Invoke-Api GET '/admin/suppliers' $t).data

  $wanted = @(
    @{ business_name = 'Boulangerie Sahel'; country_code = 'CM'; category = 'alimentation'; settlement_currency = 'XAF'; approve = $true },
    @{ business_name = 'Pharmacie Centrale'; country_code = 'CM'; category = 'sante'; settlement_currency = 'XAF'; approve = $true },
    @{ business_name = 'Lagos Market'; country_code = 'NG'; category = 'commerce'; settlement_currency = 'NGN'; approve = $true },
    @{ business_name = 'Fournisseur en attente (invisible)'; country_code = 'CM'; category = 'test'; settlement_currency = 'XAF'; approve = $false }
  )

  foreach ($s in $wanted) {
    if ($existing | Where-Object { $_.business_name -eq $s.business_name }) {
      Write-Host "= $($s.business_name) existe deja"
      continue
    }
    $body = @{ business_name = $s.business_name; country_code = $s.country_code; category = $s.category; settlement_currency = $s.settlement_currency }
    $created = (Invoke-Api POST '/admin/suppliers' $t $body).data
    if ($s.approve) { Invoke-Api POST "/admin/suppliers/$($created.id)/approve" $t | Out-Null }
    Write-Host "+ $($s.business_name) ($($s.settlement_currency))"
  }

  foreach ($c in @(@{ cur = 'XAF'; bps = 500 }, @{ cur = 'NGN'; bps = 300 })) {
    $rules = (Invoke-Api GET "/admin/commission-rules?currency=$($c.cur)&status=active" $t).data
    if ($rules | Where-Object { $null -eq $_.supplier_id }) {
      Write-Host "= regle par defaut $($c.cur) existe deja"
      continue
    }
    Invoke-Api POST '/admin/commission-rules' $t @{ currency = $c.cur; type = 'percentage'; percentage_bps = $c.bps } | Out-Null
    Write-Host "+ regle par defaut $($c.cur) : $($c.bps) points de base"
  }
}

function Dev-Users {
  $json = php artisan tinker --execute="echo App\Models\User::orderByDesc('id')->limit(5)->get(['id','email','role','status'])->toJson();"
  ($json | ConvertFrom-Json) | Format-Table -AutoSize
}

function Dev-Pending {
  $json = php artisan tinker --execute="echo App\Models\Payment::whereIn('status',['pending','requires_action'])->orderByDesc('id')->limit(10)->get(['id','reference','amount_minor','currency','status'])->toJson();"
  $rows = $json | ConvertFrom-Json
  if (-not $rows) { Write-Host 'Aucun paiement en attente.'; return }
  $rows | Format-Table -AutoSize
}

function Dev-Status {
  php artisan tinker --execute="echo 'users='.App\Models\User::count().' suppliers='.App\Models\Supplier::count().' orders='.App\Models\Order::count().' payments='.App\Models\Payment::count().' receipts='.App\Models\Receipt::count();"
}

function Get-LatestPendingId {
  $id = php artisan tinker --execute="echo App\Models\Payment::whereIn('status',['pending','requires_action'])->max('id');"
  return [int]('0' + ($id | Out-String).Trim())
}

function Send-DevEvent([int]$PaymentId, [string]$Type) {
  if ($PaymentId -le 0) { $PaymentId = Get-LatestPendingId }
  if ($PaymentId -le 0) { Write-Host 'Aucun paiement en attente.'; return }

  $json = php artisan tinker --execute="echo App\Models\Payment::find($PaymentId)->toJson();"
  $payment = $json | ConvertFrom-Json

  $evt = @{
    id = 'evt_' + [guid]::NewGuid().ToString('N')
    type = $Type
    data = @{ provider_reference = $payment.provider_reference; amount_minor = [int64]$payment.amount_minor; currency = $payment.currency }
  }
  $body = $evt | ConvertTo-Json -Compress -Depth 5
  $ts = [string][DateTimeOffset]::UtcNow.ToUnixTimeSeconds()
  $hmac = New-Object System.Security.Cryptography.HMACSHA256
  $hmac.Key = [Text.Encoding]::UTF8.GetBytes((Get-EnvValue 'SANDBOX_WEBHOOK_SECRET'))
  $sig = ([BitConverter]::ToString($hmac.ComputeHash([Text.Encoding]::UTF8.GetBytes("$ts.$body"))) -replace '-', '').ToLower()

  $result = Invoke-RestMethod -Method Post -Uri "$($global:DevBase)/webhooks/sandbox" -ContentType 'application/json' -Body $body -Headers @{ 'X-Webhook-Timestamp' = $ts; 'X-Webhook-Signature' = $sig; Accept = 'application/json' }
  Write-Host "Paiement $PaymentId : $Type -> $($result.outcome)"
}

function Dev-Confirm([int]$PaymentId = 0) { Send-DevEvent $PaymentId 'payment.succeeded' }
function Dev-Fail([int]$PaymentId = 0) { Send-DevEvent $PaymentId 'payment.failed' }

function Dev-ReceiptPdf([string]$Email, [string]$Password, [int]$PaymentId) {
  $t = (Invoke-Api POST '/auth/login' '' @{ login = $Email; password = $Password }).token
  $r = Invoke-Api GET "/me/transactions/$PaymentId/receipt" $t
  if (-not $r.data.is_proof_of_payment) { Write-Host "Pas de preuve de paiement : $($r.data.status_label)"; return }
  $id = [regex]::Match($r.data.download_url, '/me/receipts/(\d+)/download').Groups[1].Value
  $file = "$env:USERPROFILE\Desktop\recu_test.pdf"
  Invoke-WebRequest -UseBasicParsing -Uri "$($global:DevBase)/me/receipts/$id/download" -Headers @{ Authorization = "Bearer $t" } -OutFile $file
  Start-Process $file
}

function Dev-Reset {
  $answer = Read-Host 'Efface TOUTES les donnees de la base de developpement. Taper OUI pour continuer'
  if ($answer -ne 'OUI') { Write-Host 'Annule.'; return }
  php artisan migrate:fresh --seed
}

function Dev-Help {
  Write-Host 'Dev-Seed                 fournisseurs et commissions de test'
  Write-Host 'Dev-Users                5 derniers utilisateurs'
  Write-Host 'Dev-Pending              paiements en attente'
  Write-Host 'Dev-Status               compteurs (utilisateurs, commandes, paiements, recus)'
  Write-Host 'Dev-Confirm [id]         le prestataire confirme (dernier paiement en attente par defaut)'
  Write-Host 'Dev-Fail [id]            le prestataire signale un echec'
  Write-Host 'Dev-ReceiptPdf -Email -Password -PaymentId   telecharge et ouvre le PDF du recu'
  Write-Host 'Dev-Reset                reconstruit la base (efface tout)'
}
