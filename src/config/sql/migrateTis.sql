-- --------------------------------------------
-- Use this script to migrate database
--   from tkTis to tkTask
--
-- --------------------------------------------

-- Insert default table data
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_SAFE_UPDATES = 0;


-- You will need to do a find and replace to substitute for the live DB names
-- Source DB: dev_tktis
-- Dest   DB: dev_tktask

-- Import registry settings
REPLACE INTO dev_tktask.registry (`key`, value)
(
  SELECT `key`, value
  FROM dev_tktis._data
  WHERE fkey = 'system'
  AND `key` IN (
    'site.email',
    'site.email.sig',
    'site.company.id',
    'site.invoice.payment',
    'site.email.invoice.unpaid',
    'site.email.invoice.paid',
    'site.email.payment.cleared',
    'site.invoice.enable',
    'site.expenses.enable',
    'site.taskLog.billable.default',
    '')
);

UPDATE dev_tktask.registry r
LEFT JOIN dev_tktis._data d ON (r.key = 'site.name' AND d.key = 'site.title')
SET r.value = d.value
WHERE d.key IS NOT NULL;

UPDATE dev_tktask.registry r
LEFT JOIN dev_tktis._data d ON (r.key = 'site.name.short' AND d.key = 'site.short.title')
SET r.value = d.value
WHERE d.key IS NOT NULL;

UPDATE dev_tktask.registry r
SET value = '1'
WHERE `key` = value;


--
TRUNCATE dev_tktask.file;
INSERT IGNORE INTO dev_tktask.file
(
  SELECT
    id AS file_id,
    2 AS user_id,
    fkey,
    fid,
    '' AS label,
    path AS filename,
    bytes,
    mime,
    IFNULL(notes, '') AS notes,
    selected,
    created
  FROM dev_tktis.file
);

--
TRUNCATE dev_tktask.company;
INSERT IGNORE INTO dev_tktask.company
(
  SELECT
    id AS company_id,
    type,
    name,
    alias,
    abn,
    website,
    contact,
    phone,
    email,
    IFNULL(address, ''),
    credit,
    IFNULL(notes, '') AS notes,
    NOT del AS active,
    modified,
    created
  FROM dev_tktis.company
);
UPDATE dev_tktask.company SET active = FALSE WHERE company_id IN (3,6,8);

--
TRUNCATE dev_tktask.project;
INSERT IGNORE INTO dev_tktask.project
(
  SELECT
    id AS project_id,
    user_id,
    company_id,
    status,
    name,
    quote,
    DATE(date_start) AS start_on,
    DATE(date_end) AS end_on,
    IFNULL(description, ''),
    IFNULL(notes, '') AS notes,
    modified,
    created
  FROM dev_tktis.project
);
TRUNCATE dev_tktask.project_user;
INSERT IGNORE INTO dev_tktask.project_user
(
  SELECT
    project_id,
    user_id
  FROM dev_tktis.project_user
);

--
TRUNCATE dev_tktask.product_category;
INSERT IGNORE INTO dev_tktask.product_category
(
  SELECT
    id AS product_category_id,
    name,
    IFNULL(description, '') AS description,
    modified,
    created
  FROM dev_tktis.product_category
);

--
TRUNCATE dev_tktask.product;
INSERT IGNORE INTO dev_tktask.product
(
  SELECT
    id AS product_id,
    category_id,
    CASE
      -- WHEN type = 'each' THEN NULL
      WHEN type = 'bianual' THEN 'biannual'
      ELSE type
    END AS cycle,
    name,
    code,
    price,
    IFNULL(description, '') AS description,
    IFNULL(notes, '') AS notes,
    NOT del AS active,
    modified,
    created
  FROM dev_tktis.product
);
UPDATE dev_tktask.product SET cycle = 'biannual' WHERE cycle = 'bianual';

--
TRUNCATE dev_tktask.expense_category;
INSERT IGNORE INTO dev_tktask.expense_category
  (
    SELECT
      id AS expense_category_id,
      name,
      IFNULL(description, '') AS description,
      ratio,
      NOT del AS active,
      modified,
      created
    FROM dev_tktis.expense_category
  );

--
TRUNCATE dev_tktask.expense;
INSERT IGNORE INTO dev_tktask.expense
  (
    SELECT
      id AS expense_id,
      category_id,
      company_id,
      invoice_no,
      receipt_no,
      IFNULL(description, '') AS description,
      DATE(date) AS purchased_on,
      total,
      modified,
      created
    FROM dev_tktis.expense
  );

--
TRUNCATE dev_tktask.status_log;
INSERT IGNORE INTO dev_tktask.status_log
  (
    SELECT
      id AS status_log_id,
      2 AS user_id,
      fkey,
      fid,
      name,
      notify,
      message,
      NULL AS data,
      created
    FROM dev_tktis.status_log
  );

--
TRUNCATE dev_tktask.task_category;
INSERT IGNORE INTO dev_tktask.task_category
(
  SELECT
    id AS task_category_id,
    name,
    label,
    IFNULL(description, '') AS description,
    order_by,
    NOT del AS active,
    modified,
    created
  FROM dev_tktis.task_category
);

--
TRUNCATE dev_tktask.task;
INSERT IGNORE INTO dev_tktask.task
(
  SELECT
    id AS task_id,
    company_id,
    project_id,
    category_id,
    creator_user_id,
    assigned_user_id,
    closed_user_id,
    status,
    IFNULL(subject, '') AS subject,
    IFNULL(comments, '') AS comments,
    priority,
    minutes,
    invoiced,
    modified,
    created
  FROM dev_tktis.task
);

--
TRUNCATE dev_tktask.task_log;
INSERT IGNORE INTO dev_tktask.task_log
(
  SELECT
    id AS task_log_id,
    task_id,
    user_id,
    product_id,
    status,
    billable,
    date AS start_at,
    minutes,
    IFNULL(comment, '') AS comment,
    IFNULL(notes, '') AS notes,
    modified,
    created
  FROM dev_tktis.task_log
);

--
TRUNCATE dev_tktask.recurring;
INSERT IGNORE INTO dev_tktask.recurring
(
  SELECT
    id AS recurring_id,
    company_id,
    product_id,
    price,
    count,
    type AS cycle,
    DATE(date_start) AS start_on,
    DATE(date_end) AS end_on,
    DATE(last_invoice) AS prev_on,
    DATE(next_invoice) AS next_on,
    active,
    issue,
    IFNULL(description, '') AS description,
    IFNULL(notes, '') AS notes,
    modified,
    created
  FROM dev_tktis.recurring
  WHERE NOT del
);

--
TRUNCATE dev_tktask.invoice;
INSERT IGNORE INTO dev_tktask.invoice
(
  SELECT
    id AS invoice_id,
    -- account,   -- This can be generated from a view or method
    'App\\Db\\Company' AS fkey,
    CAST(REGEXP_SUBSTR(account, '[0-9]+') AS UNSIGNED) AS fid,
    discount,
    tax,
    sub_total,
    shipping,
    total,
    status,
    IFNULL(billing_address, '') AS billing_address,
    IFNULL(shipping_address, '') AS shipping_address,
    DATE(date_issued) AS issued_on,
    DATE(date_paid) AS paid_on,
    IFNULL(notes, '') AS notes,
    modified,
    created
  FROM dev_tktis.invoice
);
ANALYZE TABLE dev_tktask.invoice;

--
TRUNCATE dev_tktask.invoice_item;
INSERT IGNORE INTO dev_tktask.invoice_item
(
  SELECT
    id AS invoice_item_id,
    invoice_id,
    product_code,
    IFNULL(description, '') AS description,
    qty,
    price,
    total,
    IFNULL(notes, '') AS notes,
    modified,
    created
  FROM dev_tktis.invoice_item
  WHERE NOT del
);

--
TRUNCATE dev_tktask.payment;
INSERT IGNORE INTO dev_tktask.payment
(
  SELECT
    id AS payment_id,
    invoice_id,
    amount,
    method,
    status,
    received AS received_at,
    IFNULL(notes, '') AS notes,
    modified,
    created
  FROM dev_tktis.invoice_payment
  WHERE NOT del
);








SET SQL_SAFE_UPDATES = 1;
SET FOREIGN_KEY_CHECKS = 1;
