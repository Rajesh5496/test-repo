<?php
namespace WPSynchro\Files;

/**
 * Class for handling an instance of a location, which need file migration
 * @since 1.2.0
 */
class Location
{

    public $base = '';
    public $path = '';
    public $strategy = 'clean';
    public $is_file = false;
    public $exclusions = "";

}
