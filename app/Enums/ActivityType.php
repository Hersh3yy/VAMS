<?php

declare(strict_types=1);

namespace App\Enums;

enum ActivityType: string
{
    case CREATE = 'create';
    case UPDATE = 'update';
    case DELETE = 'delete';
    case LOGIN = 'login';
    case LOGOUT = 'logout';
    case REGISTER = 'register';
    case UPLOAD = 'upload';
    case DOWNLOAD = 'download';
    case SHARE = 'share';
    case EXPORT = 'export';
    case IMPORT = 'import';
    case RESTORE = 'restore';

    /**
     * Get all activity type values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get activity type labels for display
     */
    public function label(): string
    {
        return match ($this) {
            self::CREATE => 'Created',
            self::UPDATE => 'Updated',
            self::DELETE => 'Deleted',
            self::LOGIN => 'Logged In',
            self::LOGOUT => 'Logged Out',
            self::REGISTER => 'Registered',
            self::UPLOAD => 'Uploaded',
            self::DOWNLOAD => 'Downloaded',
            self::SHARE => 'Shared',
            self::EXPORT => 'Exported',
            self::IMPORT => 'Imported',
            self::RESTORE => 'Restored',
        };
    }

    /**
     * Get activity type icons for display
     */
    public function icon(): string
    {
        return match ($this) {
            self::CREATE => 'plus-circle',
            self::UPDATE => 'pencil',
            self::DELETE => 'trash',
            self::LOGIN => 'login',
            self::LOGOUT => 'logout',
            self::REGISTER => 'user-plus',
            self::UPLOAD => 'upload',
            self::DOWNLOAD => 'download',
            self::SHARE => 'share',
            self::EXPORT => 'download',
            self::IMPORT => 'upload',
            self::RESTORE => 'refresh',
        };
    }

    /**
     * Get activity type colors for display
     */
    public function color(): string
    {
        return match ($this) {
            self::CREATE => 'green',
            self::UPDATE => 'blue',
            self::DELETE => 'red',
            self::LOGIN => 'green',
            self::LOGOUT => 'gray',
            self::REGISTER => 'blue',
            self::UPLOAD => 'purple',
            self::DOWNLOAD => 'indigo',
            self::SHARE => 'yellow',
            self::EXPORT => 'teal',
            self::IMPORT => 'orange',
            self::RESTORE => 'pink',
        };
    }

    /**
     * Check if this is a creation activity
     */
    public function isCreate(): bool
    {
        return $this === self::CREATE;
    }

    /**
     * Check if this is an update activity
     */
    public function isUpdate(): bool
    {
        return $this === self::UPDATE;
    }

    /**
     * Check if this is a deletion activity
     */
    public function isDelete(): bool
    {
        return $this === self::DELETE;
    }

    /**
     * Check if this is an authentication activity
     */
    public function isAuth(): bool
    {
        return in_array($this, [self::LOGIN, self::LOGOUT, self::REGISTER]);
    }

    /**
     * Check if this is a file operation activity
     */
    public function isFileOperation(): bool
    {
        return in_array($this, [self::UPLOAD, self::DOWNLOAD, self::EXPORT, self::IMPORT]);
    }
}
