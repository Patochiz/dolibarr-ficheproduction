<?php
/**
 * \file        includes/ficheproduction-permissions.php
 * \ingroup     ficheproduction
 * \brief       Gestion des permissions et chargement des objets pour FicheProduction
 */

// Prevent direct access
if (!defined('DOL_VERSION')) {
    print "Error: This module requires Dolibarr framework.\n";
    exit;
}

/**
 * Check permissions and load order object
 * 
 * @param int    $id    Order ID
 * @param string $ref   Order reference
 * @param User   $user  Current user
 * @param DoliDB $db    Database connection
 * @return Commande     Order object
 */
function checkPermissionsAndLoadOrder($id, $ref, $user, $db) 
{
    // Initialize objects
    $object = new Commande($db);
    $form = new Form($db);

    // Check permissions
    if (!$user->rights->commande->lire) {
        accessforbidden();
    }

    // Load object
    if ($id > 0 || !empty($ref)) {
        $result = $object->fetch($id, $ref);
        if ($result <= 0) {
            dol_print_error($db, $object->error);
            exit;
        }
        
        // Load the thirdparty object
        if (method_exists($object, 'fetch_thirdparty')) {
            $object->fetch_thirdparty();
        }
        
        // Load lines and extrafields
        $object->fetch_lines();
        $object->fetch_optionals();
    } else {
        header('Location: '.dol_buildpath('/commande/list.php', 1));
        exit;
    }
    
    return $object;
}

/**
 * Check if user can edit orders
 * 
 * @param User $user Current user
 * @return bool      True if user can edit
 */
function checkUserCanEdit($user) 
{
    return $user->rights->commande->creer ?? false;
}
?>