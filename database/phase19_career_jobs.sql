-- Star Parts Motor Phase 19: editable career vacancy listings.

CREATE TABLE IF NOT EXISTS career_jobs (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    poster VARCHAR(255) NULL,
    excerpt TEXT NULL,
    description MEDIUMTEXT NULL,
    application_url VARCHAR(500) NULL,
    application_label VARCHAR(100) NOT NULL DEFAULT 'Lamar Sekarang',
    status ENUM('draft','published') NOT NULL DEFAULT 'draft',
    sort_order INT NOT NULL DEFAULT 0,
    published_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_career_jobs_slug (slug),
    KEY idx_career_jobs_status (status),
    KEY idx_career_jobs_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
