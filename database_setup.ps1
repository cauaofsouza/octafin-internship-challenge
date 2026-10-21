# database_setup.ps1
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "Iniciando o setup do banco de dados (OctaFin)..." -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan

$containerName = "octafin_postgres"
$dbName = "octafin_db"

# 1. Verifica se o container está rodando
Write-Host "`n[1/3] Verificando se o container '$containerName' está ativo..." -ForegroundColor Yellow
$containerStatus = docker inspect -f '{{.State.Running}}' $containerName 2>$null

if ($containerStatus -ne "true") {
    Write-Host "Erro: O container '$containerName' não está rodando." -ForegroundColor Red
    exit 1
}
Write-Host "Container ativo!" -ForegroundColor Green


# 2. Verifica se o banco de dados já existe
Write-Host "`n[2/3] Verificando existência do banco de dados '$dbName'..." -ForegroundColor Yellow
$dbCheck = docker exec -t $containerName psql -U postgres -tAc "SELECT 1 FROM pg_database WHERE datname='$dbName'" 2>$null

if ($dbCheck.Trim() -eq "1") {
    Write-Host "O banco de dados '$dbName' JÁ EXISTE. Nenhuma migration foi executada para evitar sobrescrever dados." -ForegroundColor Yellow
    Write-Host "Processo finalizado sem alterações." -ForegroundColor Green
    exit 0
}

# 4. Cria o banco de dados e executa as migrations se o banco NÃO existir
Write-Host "Criando '$dbName'..." -ForegroundColor Yellow
docker exec -t $containerName psql -U postgres -c "CREATE DATABASE $dbName;"

Write-Host "`n[3/3] Executando as Migrations do Laravel..." -ForegroundColor Yellow
docker exec -t octafin_app php artisan migrate

# 5. Seeds (Comentado para o futuro)
# Write-Host "`n[4/4] Executando as Seeds..." -ForegroundColor Yellow
# docker exec -t octafin_app php artisan db:seed

Write-Host "`n==========================================" -ForegroundColor Cyan
Write-Host "Setup do banco de dados concluído com sucesso! 🚀" -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Cyan