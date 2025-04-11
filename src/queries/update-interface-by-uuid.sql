UPDATE interfaces AS i
SET i.label = :label, i.logo = :logo, i.content = :content, i.webhook = :webhook, i.updated_at = NOW()
WHERE i.uuid = :uuid
