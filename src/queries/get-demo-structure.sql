SELECT
    i.*,
    u.name AS owner_name,
    w.url AS server_url,
    w.secret AS server_secret
FROM structures AS i
        INNER JOIN users AS u ON u.id = i.created_by
        LEFT JOIN webhooks AS w ON w.uuid = i.webhook
WHERE i.hash = "demo"
GROUP BY i.uuid
