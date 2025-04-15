SELECT DISTINCT combined_results.uuid, combined_results.*
FROM (
         SELECT w.*
         FROM webhooks AS w
         WHERE w.created_by = :userId

         UNION ALL

         SELECT w.*
         FROM permissions AS p
                  INNER JOIN users AS u ON u.email = p.email
                  INNER JOIN interfaces AS i ON i.uuid = p.interface_uuid
                  INNER JOIN users AS owner ON owner.id = i.created_by
                  LEFT JOIN webhooks AS w ON w.uuid = i.webhook
         WHERE u.id = :userId
     ) AS combined_results