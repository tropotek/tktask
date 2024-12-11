-- ------------------------------------------------------
-- SQL views
-- ------------------------------------------------------

-- \App\Db\User
CREATE OR REPLACE VIEW v_user AS
SELECT
  u.*,
  TRIM(CONCAT_WS(' ', u.given_name, u.family_name)) AS name_short,
  TRIM(CONCAT_WS(' ', u.title , u.given_name, u.family_name)) AS name_long,
  IFNULL(a.uid, '') AS uid,
  IFNULL(a.permissions, 0) AS permissions,
  IFNULL(a.username, '') AS username,
  IFNULL(a.email, '') AS email,
  IFNULL(a.timezone, '') AS timezone,
  IFNULL(a.active, FALSE) AS active,
  IFNULL(a.session_id, '') AS session_id,
  a.last_login,
  MD5(CONCAT(a.auth_id, 'Auth')) AS hash,
  CONCAT('/app/', u.type, '/' , u.user_id, '/data') AS data_path
FROM user u
       LEFT JOIN auth a ON (a.fkey = 'App\\Db\\User' AND a.fid = (u.user_id))
;

-- \App\Db\Notify
CREATE OR REPLACE VIEW v_notify AS
SELECT
  n.*,
  read_at IS NOT NULL AS is_read,
  notified_at IS NOT NULL AS is_notified
FROM notify n
;

-- \App\Db\File
CREATE OR REPLACE VIEW v_file AS
SELECT
  f.*,
  MD5(CONCAT(f.file_id, 'File')) AS hash
FROM file f
;

-- \App\Db\Company
CREATE OR REPLACE VIEW v_company AS
SELECT
  c.*,
  CONCAT("CO-", LPAD(c.company_id, 10, "0")) AS account_id
FROM company c
;