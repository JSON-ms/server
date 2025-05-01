SELECT
    i.*,
    u.name AS owner_name,
    e.url AS server_url,
    e.secret AS server_secret
FROM structures AS i
        INNER JOIN users AS u ON u.id = i.created_by
        LEFT JOIN endpoints AS e ON e.uuid = i.endpoint
WHERE i.hash = "demo"
GROUP BY i.uuid
