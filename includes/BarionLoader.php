<?php
namespace FluentBarion\BarionWrapper;
use FluentBarion\BarionProcessor\BarionProcessor;
use FluentBarion\BarionHandler\BarionHandler;
use FluentBarion\BarionSettings\BarionSettings;
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
class BarionLoader {

        public static function load() {
        
        if (!class_exists('BarionClient')) {
    require_once plugin_dir_path(__FILE__) . '../lib/autoload.php';
}
        if (!class_exists('BarionHandler')) {
            require_once plugin_dir_path( __FILE__ ) . 'BarionHandler.php';
        }

                if (!class_exists('BarionProcessor')) {
            require_once plugin_dir_path( __FILE__ ) . 'BarionProcessor.php';
        }

        if (!class_exists('FluentBarion\BarionSettings\BarionSettings')) {
    require_once plugin_dir_path(__FILE__) . 'BarionSettings.php';
    //new BarionSettings();
}
                    }
}