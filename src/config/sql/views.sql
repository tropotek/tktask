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

-- \App\Db\Recurring
CREATE OR REPLACE VIEW v_recurring AS
SELECT
  r.recurring_id,
  r.company_id,
  r.product_id,
  COALESCE(r.price, p.price, 0) AS price,   -- default to 0 if no price or product set
  r.count,
  r.cycle,
  r.start_on,
  r.end_on,
  r.prev_on,
  r.next_on,
  r.active,
  r.issue,
  r.description,
  r.notes,
  r.modified,
  r.created
FROM recurring r
LEFT JOIN product p USING (product_id)
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
  IFNULL(c.name, '') AS company_name,
  IFNULL(ass.name_short, '') AS assigned_name,
  CASE
      WHEN closed_at IS NOT NULL THEN 'closed'
      WHEN cancelled_at IS NOT NULL THEN 'cancelled'
      ELSE 'open'
  END AS status,
  CONCAT('/task/', YEAR(t.created), '/' , t.task_id) AS data_path
FROM task t
LEFT JOIN company c USING (company_id)
LEFT JOIN v_user ass ON (ass.user_id = t.assigned_user_id);

-- \App\Db\TaskLog
CREATE OR REPLACE VIEW v_task_log AS
SELECT
  tl.*,
  t.data_path,
  t.status,
  IFNULL(p.name, '') AS product_name
FROM task_log tl
JOIN v_task t USING (task_id)
LEFT JOIN product p USING (product_id);

-- \App\Db\Project
CREATE OR REPLACE VIEW v_project AS
SELECT
  p.*,
  CASE
    WHEN p.cancelled_on IS NOT NULL THEN 'cancelled'
    WHEN CURRENT_DATE BETWEEN p.start_on AND p.end_on THEN 'active'
    WHEN CURRENT_DATE > p.end_on THEN 'completed'
    ELSE 'pending'
  END AS status
FROM project p;

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
  CASE
      WHEN i.cancelled_on IS NOT NULL THEN 'cancelled'
      WHEN i.paid_on IS NOT NULL THEN 'paid'
      WHEN i.issued_on IS NOT NULL THEN 'unpaid'
      ELSE 'open'
  END AS status,
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
    domain_ping_id,
    domain_id,
    status,
    site_name,
    bytes,
    created,
		ROW_NUMBER() OVER (PARTITION BY domain_id ORDER BY created DESC) AS latest
  FROM domain_ping
),
latest_online AS (
  SELECT
    domain_id,
    created,
		ROW_NUMBER() OVER (PARTITION BY domain_id ORDER BY created DESC) AS latest
  FROM domain_ping
  WHERE status
)
SELECT
  d.*,
  c.name AS company_name,
  IFNULL(lp.status, FALSE) AS status,
  IFNULL(lp.bytes, 0) AS bytes,
  IFNULL(lp.site_name, '') AS site_name,
  lo.created AS pinged_at,
  lp.domain_ping_id AS last_ping_id
FROM domain d
JOIN company c USING (company_id)
LEFT JOIN latest_pings lp ON (d.domain_id = lp.domain_id AND lp.latest = 1)
LEFT JOIN latest_online lo ON (d.domain_id = lo.domain_id AND lo.latest = 1)
;