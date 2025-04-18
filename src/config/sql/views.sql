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

-- \App\Db\Product
CREATE OR REPLACE VIEW v_product AS
SELECT
  p.*,
  pc.name AS category_name
FROM product p
JOIN product_category pc USING (product_category_id)
;

-- \App\Db\Company
CREATE OR REPLACE VIEW v_company AS
SELECT
  c.*,
  CONCAT('CO-', LPAD(c.company_id, 10, '0')) AS account_id
FROM company c
;

-- \App\Db\Expense
CREATE OR REPLACE VIEW v_expense AS
SELECT
  e.*,
  CONCAT('/expense/', YEAR(e.created), '/' , e.expense_id) AS data_path
FROM expense e;

-- \App\Db\Task
CREATE OR REPLACE VIEW v_task AS
SELECT
  t.*,
  CONCAT('/task/', YEAR(t.created), '/' , t.task_id) AS data_path
FROM task t;

-- \App\Db\TaskLog
CREATE OR REPLACE VIEW v_task_log AS
SELECT
  tl.*,
  t.data_path
FROM task_log tl
JOIN v_task t USING (task_id);

-- \App\Db\InvoiceItem
CREATE OR REPLACE VIEW v_invoice_item AS
SELECT
  it.*
FROM invoice_item it
;

-- \App\Db\Invoice
CREATE OR REPLACE VIEW v_invoice AS
WITH sub_totals AS (
  SELECT
    invoice_id,
    SUM(ROUND(qty*price)) AS sub_total
  FROM invoice_item
  GROUP BY invoice_id
),
payments AS (
  SELECT
    invoice_id,
    SUM(amount) AS paid_total
  FROM payment
  WHERE status = 'cleared'
  GROUP BY invoice_id
),
totals AS (
  SELECT
    invoice_id,
    IFNULL(st.sub_total, 0) AS sub_total,
    IFNULL(ROUND(st.sub_total * i.discount), 0) AS discount_total,
    IFNULL(ROUND((st.sub_total - (st.sub_total * i.discount)) * i.tax), 0) AS tax_total,
    IFNULL(ROUND(
      ((st.sub_total -                                                     --
      IFNULL((st.sub_total * i.discount), 0)) +                            -- subtract discount
      IFNULL((st.sub_total - (st.sub_total * i.discount)) * i.tax, 0)) +   -- Add tax
      i.shipping
    ), 0) AS total,
    IFNULL(p.paid_total, 0) AS paid_total
  FROM invoice i
  LEFT JOIN sub_totals st USING (invoice_id)
  LEFT JOIN payments p USING (invoice_id)
)
SELECT
  i.*,
  t.sub_total,
  t.discount_total,
  t.tax_total,
  t.total,
  t.paid_total,
  ROUND(t.total - t.paid_total, 0) AS unpaid_total
FROM invoice i
LEFT JOIN totals t USING (invoice_id)
;

-- \App\Db\Domain
CREATE OR REPLACE VIEW v_domain AS
WITH latest_pings AS (
  SELECT
    domain_id,
    status,
    site_name,
    bytes,
    created,
		ROW_NUMBER() OVER (PARTITION BY domain_id ORDER BY created DESC) AS latest
  FROM domain_ping
)
SELECT
  d.*,
  c.name AS company_name,
  IFNULL(lp.status, FALSE) AS status,
  IFNULL(lp.bytes, 0) AS bytes,
  IFNULL(lp.site_name, '') AS site_name,
  lp.created AS last_ping_time
FROM domain d
JOIN company c USING (company_id)
LEFT JOIN latest_pings lp ON (d.domain_id = lp.domain_id AND lp.latest = 1)
;