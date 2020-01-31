<?php



/**
 * Register the classes
 */
ClassLoader::addClasses(array(
    // Models
    'Lumturo\ContaoTF2Bundle\Model\BookingModel' => 'vendor/lumturo/contao-tf2-bundle/src/Resources/contao/models/BookingModel.php',
    'Lumturo\ContaoTF2Bundle\Model\EmailModel' => 'vendor/lumturo/contao-tf2-bundle/src/Resources/contao/models/EmailModel.php',
    'Lumturo\ContaoTF2Bundle\Model\DocumentModel' => 'vendor/lumturo/contao-tf2-bundle/src/Resources/contao/models/DocumentModel.php',
    'Lumturo\ContaoTF2Bundle\Model\SeasonModel' => 'vendor/lumturo/contao-tf2-bundle/src/Resources/contao/models/SeasonModel.php',
    // Modules
    // Classes
    'Lumturo\ContaoTF2Bundle\Invoice' => 'vendor/lumturo/contao-tf2-bundle/src/Resources/contao/classes/Invoice.php',
    'Lumturo\ContaoTF2Bundle\TF2Invoice' => 'vendor/lumturo/contao-tf2-bundle/src/Resources/contao/classes/TF2Invoice.php',
    'Lumturo\ContaoTF2Bundle\Mailbox' => 'vendor/lumturo/contao-tf2-bundle/src/Resources/contao/classes/Mailbox.php'

));
