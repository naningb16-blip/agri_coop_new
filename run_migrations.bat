@echo off
set MYSQL=C:\mysql8\bin\mysql.exe
set H=mysql-1e56207f-dsuanzon2004-8667.e.aivencloud.com
set P=
set U=avnadmin
set DB=defaultdb
set SSL=config/aiven-ca.pem

echo Running schema.sql...
%MYSQL% --ssl-ca=%SSL% -h %H% -P %P% -u %U% -p%PW% %DB% < database/schema.sql

echo Running rbac_migration.sql...
%MYSQL% --ssl-ca=%SSL% -h %H% -P %P% -u %U% -p%PW% %DB% < database/rbac_migration.sql

echo Running approval_system.sql...
%MYSQL% --ssl-ca=%SSL% -h %H% -P %P% -u %U% -p%PW% %DB% < database/approval_system.sql

echo Running modules_migration.sql...
%MYSQL% --ssl-ca=%SSL% -h %H% -P %P% -u %U% -p%PW% %DB% < database/modules_migration.sql

echo Running notifications_migration.sql...
%MYSQL% --ssl-ca=%SSL% -h %H% -P %P% -u %U% -p%PW% %DB% < database/notifications_migration.sql

echo Running inventory_migration.sql...
%MYSQL% --ssl-ca=%SSL% -h %H% -P %P% -u %U% -p%PW% %DB% < database/inventory_migration.sql

echo Running purchasing_migration.sql...
%MYSQL% --ssl-ca=%SSL% -h %H% -P %P% -u %U% -p%PW% %DB% < database/purchasing_migration.sql

echo Running logistics_migration.sql...
%MYSQL% --ssl-ca=%SSL% -h %H% -P %P% -u %U% -p%PW% %DB% < database/logistics_migration.sql

echo Running processing_migration.sql...
%MYSQL% --ssl-ca=%SSL% -h %H% -P %P% -u %U% -p%PW% %DB% < database/processing_migration.sql

echo Running finance_migration.sql...
%MYSQL% --ssl-ca=%SSL% -h %H% -P %P% -u %U% -p%PW% %DB% < database/finance_migration.sql

echo Running payroll_migration.sql...
%MYSQL% --ssl-ca=%SSL% -h %H% -P %P% -u %U% -p%PW% %DB% < database/payroll_migration.sql

echo Running ledger_migration.sql...
%MYSQL% --ssl-ca=%SSL% -h %H% -P %P% -u %U% -p%PW% %DB% < database/ledger_migration.sql

echo Running receipt_migration.sql...
%MYSQL% --ssl-ca=%SSL% -h %H% -P %P% -u %U% -p%PW% %DB% < database/receipt_migration.sql

echo Running document_routing_migration.sql...
%MYSQL% --ssl-ca=%SSL% -h %H% -P %P% -u %U% -p%PW% %DB% < database/document_routing_migration.sql

echo Running document_routing_v2.sql...
%MYSQL% --ssl-ca=%SSL% -h %H% -P %P% -u %U% -p%PW% %DB% < database/document_routing_v2.sql

echo.
echo All migrations done!
pause
