SELECT DISTINCT combined_results.uuid, combined_results.*
FROM (
         SELECT e.*
         FROM endpoints AS e
         WHERE e.created_by = :userId

         UNION ALL

         SELECT e.*
         FROM permissions AS p
                  INNER JOIN users AS u ON u.email = p.email
                  INNER JOIN structures AS i ON i.uuid = p.structure_uuid
                  INNER JOIN users AS owner ON owner.id = i.created_by
                  LEFT JOIN endpoints AS e ON e.uuid = i.endpoint
         WHERE u.id = :userId
     ) AS combined_results