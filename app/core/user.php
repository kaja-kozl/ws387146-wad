<?php
namespace app\core;

abstract class User extends DbModel {
    abstract public function getDisplayName(): string;
}
?>