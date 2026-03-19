<?php

/**
 * Bootstrap file for DALT learning platform
 * This file is only loaded when .dalt directory exists
 */

// Autoload .dalt/Core classes
spl_autoload_register(function ($class) {
    // Only handle Core namespace classes that might be in .dalt
    if (str_starts_with($class, 'Core\\')) {
        $className = substr($class, 5); // Remove 'Core\' prefix
        $daltPath = base_path('.dalt/Core/' . $className . '.php');
        
        if (file_exists($daltPath)) {
            require_once $daltPath;
            return true;
        }
    }
    return false;
});
