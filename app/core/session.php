<?php
namespace app\core;

class Session {

    # Contains the flash message to display when requested
    protected const FLASH_KEY = 'flash_messages';

    # Creates a session on instantiation
    public function __construct() {
    
        # Creates/resumes a session based on session identifier or cookie
        # Creates a session variable called FLASH_KEY
        session_start();
        if (!isset($_SESSION[self::FLASH_KEY])) {
            $_SESSION[self::FLASH_KEY] = [];
        }

        # Load existing flash messages
        $flashMessages = $_SESSION[self::FLASH_KEY] ?? [];

        # Marks them to be removed after showing once
        foreach ($flashMessages as $key => &$flashMessage) {
            $flashMessage['remove'] = true;
        }

        # Sets the session variable as the messages
        $_SESSION[self::FLASH_KEY] = $flashMessages;
    }

    # Enables creation of flash messages
    public function setFlash($key, $message) {
        $_SESSION[self::FLASH_KEY][$key] = [
            'remove' => false, # Demonstrates the message is new
            'value' => $message
        ];
    }

    # Enables retreival of flash messages, removing it from the session once accessed
    public function getFlash($key) {
        if (isset($_SESSION[self::FLASH_KEY][$key])) {
            $message = $_SESSION[self::FLASH_KEY][$key];
            unset($_SESSION[self::FLASH_KEY][$key]);
            return $message;
        }
        return null;
    }

    # Setting any necessary attributes in the session
    public function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    # Retrival of anything that is stored in the session
    public function get($key) {
        return $_SESSION[$key] ?? false;
    }

    # Removing any keys from the session (enables logging out)
    public function remove($key) {
        unset($_SESSION[$key]);
    }

    # When there are no more references to this session as an object, remove all flash messages marked for deletion
    public function __destruct() {
        $flashMessages = $_SESSION[self::FLASH_KEY] ?? [];
        foreach ($flashMessages as $key => $flashMessage) {
            if ($flashMessage['remove']) {
                unset($_SESSION[self::FLASH_KEY][$key]);
            }
        }
    }
}

?>