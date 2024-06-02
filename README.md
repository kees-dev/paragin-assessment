# Paragin Assessment
## Installation
Since DDEV is my dockerised dev environment of choice, I used that to run this code.

- have ddev installed https://ddev.readthedocs.io/en/stable/users/install/ddev-installation/
- run 'ddev start'
- put "Assignment.xlsx" file in /data folder
- run 'ddev ssh'
- run 'composer install'
- run 'php bin/console doctrine:migrations:migrate'
- run 'php bin/console import:score-spreadsheet'
- visit https://paragin-assessment.ddev.site/