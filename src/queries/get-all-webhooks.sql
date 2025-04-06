SELECT
    w.*
FROM webhooks AS w
WHERE
    w.created_by = :userId
