SELECT
    i.*,
    GROUP_CONCAT(DISTINCT(pi.email)) as permission_structure,
    GROUP_CONCAT(DISTINCT(pa.email)) as permission_admin,
    'owner' AS type,
    u.name AS owner_name,
    w.url AS server_url,
    w.secret AS server_secret
FROM structures AS i
    INNER JOIN users AS u ON u.id = i.created_by
    LEFT JOIN permissions AS pa ON pa.structure_uuid = i.uuid AND pa.type = 'admin'
    LEFT JOIN permissions AS pi ON pi.structure_uuid = i.uuid AND pi.type = 'structure'
    LEFT JOIN webhooks AS w ON w.uuid = i.webhook
WHERE i.uuid = :uuid
GROUP BY
    i.uuid, u.name;
