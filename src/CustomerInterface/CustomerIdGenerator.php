<?php declare(strict_types=1);

namespace FoxentryApiTask\CustomerInterface;

final class CustomerIdGenerator
{
    public static function generate(): string
    {
        $id = md5(uniqid());

        return $id;
    }
}