SELECT
    i.*,
    GROUP_CONCAT(DISTINCT(pi.email)) as permission_interface,
    GROUP_CONCAT(DISTINCT(pa.email)) as permission_admin,
    'owner' AS type,
    u.name AS owner_name,
    w.url AS server_url,
    w.secret AS server_secret
FROM interfaces AS i
    INNER JOIN users AS u ON u.id = i.created_by
    LEFT JOIN permissions AS pa ON pa.interface_uuid = i.uuid AND pa.type = 'admin'
    LEFT JOIN permissions AS pi ON pi.interface_uuid = i.uuid AND pi.type = 'interface'
    LEFT JOIN webhooks AS w ON w.uuid = i.webhook
WHERE i.created_by = :userId
GROUP BY i.uuid

UNION ALL

SELECT i.*,
    null AS permission_interface,
    null AS permission_admin,
    GROUP_CONCAT(DISTINCT(p.type)) as type,
    owner.name AS owner_name,
    w.url AS server_url,
    w.secret AS server_secret
FROM permissions AS p
    INNER JOIN users AS u ON u.email = p.email
    INNER JOIN interfaces AS i ON i.uuid = p.interface_uuid
    INNER JOIN users AS owner ON owner.id = i.created_by
    LEFT JOIN webhooks AS w ON w.uuid = i.webhook
WHERE u.id = :userId
GROUP BY i.uuid
