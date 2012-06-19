<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Text Plain Link Transformations plugin for phpMyAdmin
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage Link
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the link transformations interface */
require_once "abstract/LinkTransformationsPlugin.class.php";

/**
 * Handles the link transformation for text plain
 *
 * @package PhpMyAdmin
 */
class Text_Plain_Link extends LinkTransformationsPlugin
{
    /**
     * Gets the transformation description of the specific plugin
     *
     * @return string
     */
    public static function getInfo()
    {
        return __(
            'Displays a link; the column contains the filename. The first option'
            . ' is a URL prefix like "http://www.example.com/". The second option'
            . ' is a title for the link.'
        );
    }

    /**
     * Gets the plugin`s MIME type
     *
     * @return string
     */
    public static function getMIMEType()
    {
        return "Text";
    }

    /**
     * Gets the plugin`s MIME subtype
     *
     * @return string
     */
    public static function getMIMESubtype()
    {
        return "Plain";
    }
}
?>