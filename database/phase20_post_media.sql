-- Star Parts Motor Phase 20: external social/video links for news posts.

SET @db := DATABASE();

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE posts ADD COLUMN video_url VARCHAR(500) NULL AFTER featured_image',
        'SELECT 1'
    )
    FROM information_schema.columns
    WHERE table_schema = @db
      AND table_name = 'posts'
      AND column_name = 'video_url'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE posts ADD COLUMN is_featured TINYINT(1) NOT NULL DEFAULT 0',
        'SELECT 1'
    )
    FROM information_schema.columns
    WHERE table_schema = @db
      AND table_name = 'posts'
      AND column_name = 'is_featured'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
