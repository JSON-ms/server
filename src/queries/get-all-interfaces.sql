SELECT
    i.*,
    GROUP_CONCAT(DISTINCT(pi.email)) as permission_interface,
    GROUP_CONCAT(DISTINCT(pa.email)) as permission_admin,
    'owner' AS type,
    u.name AS owner_name
FROM interfaces AS i
         INNER JOIN users AS u ON u.id = i.created_by
         LEFT JOIN permissions AS pa ON pa.interface_uuid = i.uuid AND pa.type = 'admin'
         LEFT JOIN permissions AS pi ON pi.interface_uuid = i.uuid AND pi.type = 'interface'
WHERE i.created_by = :userId
GROUP BY i.uuid

UNION

SELECT i.*,
       null AS permission_interface,
       null AS permission_admin,
       p.type,
       owner.name AS owner_name
FROM permissions AS p
INNER JOIN users AS u ON u.email = p.email
INNER JOIN interfaces AS i ON i.uuid = p.interface_uuid
INNER JOIN users AS owner ON owner.id = i.created_by
WHERE u.id = :userId
