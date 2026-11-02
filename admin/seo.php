<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_admin();
redirect(base_url('admin/content.php?section=seo#seo-health'));
