#!/bin/bash
# Script de Backup Automático - Sistema CSI
FECHA=$(date +"%Y-%m-%d_%H-%M-%S")
BACKUP_FILE="/opt/lampp/htdocs/sistema_csi/backups/backup_auto_${FECHA}.sql"

# Crear backup
mysqldump --user=root --password= --host=localhost sistema_csi > "${BACKUP_FILE}"

# Comprimir backup
gzip "${BACKUP_FILE}"

# Eliminar backups antiguos (más de 30 días)
find /opt/lampp/htdocs/sistema_csi/backups -name "backup_auto_*.sql.gz" -mtime +30 -delete

echo "Backup automático completado: ${BACKUP_FILE}.gz"
