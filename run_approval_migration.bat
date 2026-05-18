@echo off
echo Running approval chain migration...
echo This will remove manager/department head from approval chains
echo GM will become the sole approver for all departments
echo.
pause
echo.

REM Run the migration
mysql -u root -p agri_coop < database/remove_manager_approval_step.sql

echo.
echo Migration completed!
echo GM is now the only approver for all department requests.
pause