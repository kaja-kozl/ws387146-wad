<?php
namespace app\core;

class PermissionsService
{
    private const ROLE_HIERARCHY = [
        'user'       => 0,
        'admin'      => 1,
        'super_user' => 2,
    ];

    // Permissions for each role and resource (including permissions for resources one owns)
    private const PERMISSIONS = [
        'super_user' => [
            'course' => ['add', 'list', 'view', 'edit', 'delete', 'enrol', 'unenrol', 'manage_attendees', 'view_attendees'],
            'user'   => ['create', 'list', 'edit', 'delete'],
        ],
        'admin' => [
            'course' => ['add', 'list', 'view', 'edit.own', 'delete.own', 'enrol', 'unenrol', 'manage_attendees.own', 'view_attendees.own'],
            'user'   => ['list', 'edit', 'delete'],
        ],
        'user' => [
            'course' => ['list', 'view', 'enrol', 'unenrol'],
            'user'   => ['edit.own', 'delete.own'],
        ],
    ];

    // Check if the user has permission to perform an action on a resource
    public static function can(string $action, string $resource, ?object $subject = null): bool
    {
        $user = Application::$app->user;
        if (!$user) return false;

        $role = strtolower($user->accessLevel);
        if (!isset(self::PERMISSIONS[$role][$resource])) return false;

        $allowed = self::PERMISSIONS[$role][$resource];

        // Direct permission match
        if (in_array($action, $allowed, true)) return true;

        // Ownership-scoped permission
        if ($subject !== null && in_array("$action.own", $allowed, true)) {
            return self::isOwner($user, $subject);
        }

        return false;
    }

    // Check if the user has at least the specified role
    public static function atLeast(string $role): bool
    {
        $user = Application::$app->user;
        if (!$user) return false;

        $userLevel     = self::ROLE_HIERARCHY[strtolower($user->accessLevel)] ?? -1;
        $requiredLevel = self::ROLE_HIERARCHY[$role] ?? 0;

        return $userLevel >= $requiredLevel;
    }

    // Check if the user is the owner of the subject (e.g., course lecturer or user profile)
    private static function isOwner(object $user, object $subject): bool
    {
        if (property_exists($subject, 'lecturer')) {
            $left  = (string)$subject->lecturer;
            $right = (string)$user->uid;
            $result = trim($left) === trim($right);
            return $result;
        }
        if (property_exists($subject, 'uid')) {
            $left  = (string)$subject->uid;
            $right = (string)$user->uid;
            $result = trim($left) === trim($right);
            return $result;
        }
        return false;
    }
}
?>