<?php

declare(strict_types=1);

namespace Rinart73\DocumentHelper\Config;

use CodeIgniter\Config\BaseService;
use Rinart73\DocumentHelper\Document;
use Rinart73\DocumentHelper\DocumentImages;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    public static function document(?string $publicDir = null, bool $getShared = true): Document
    {
        if ($getShared) {
            return static::getSharedInstance('document', $publicDir);
        }

        return new Document($publicDir);
    }

    /**
     * @param string|null $publicDir Absolute path to the public directory. FCPATH by default.
     */
    public static function documentImages(?string $publicDir = null, bool $getShared = true): DocumentImages
    {
        if ($getShared) {
            return static::getSharedInstance('documentImages', $publicDir);
        }

        return new DocumentImages($publicDir);
    }
}
