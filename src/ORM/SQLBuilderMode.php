<?php
namespace PHPTools\ORM;

enum SQLBuilderMode {
    case SELECT;
    case INSERT;
    case UPDATE;
    case DELETE;
}