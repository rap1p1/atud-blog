#!/bin/bash
set -euo pipefail

DB_NAME="${DB_NAME:-atud_blog}"
DB_USER="${DB_USER:-atud_app}"
DB_PASSWORD="${DB_PASSWORD:?Thiếu DB_PASSWORD}"

db_name_sql="${DB_NAME//\`/\`\`}"
db_user_sql="${DB_USER//\'/\'\'}"
db_password_sql="${DB_PASSWORD//\'/\'\'}"

mysql --protocol=socket -uroot -p"${MYSQL_ROOT_PASSWORD}" <<SQL
DELETE FROM mysql.user WHERE User = '';
DROP USER IF EXISTS '${db_user_sql}'@'%';
CREATE USER '${db_user_sql}'@'%' IDENTIFIED BY '${db_password_sql}';
GRANT SELECT, INSERT, UPDATE, DELETE ON \`${db_name_sql}\`.* TO '${db_user_sql}'@'%';
FLUSH PRIVILEGES;
SQL
