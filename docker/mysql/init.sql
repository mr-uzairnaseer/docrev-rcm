CREATE DATABASE IF NOT EXISTS docrev_ehr CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS docrev_billing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS docrev_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

GRANT ALL PRIVILEGES ON docrev_ehr.* TO 'docrev'@'%';
GRANT ALL PRIVILEGES ON docrev_billing.* TO 'docrev'@'%';
GRANT ALL PRIVILEGES ON docrev_portal.* TO 'docrev'@'%';
FLUSH PRIVILEGES;
