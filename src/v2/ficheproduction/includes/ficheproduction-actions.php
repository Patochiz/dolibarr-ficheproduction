<?php
/**
 * \file        includes/ficheproduction-actions.php
 * \ingroup     ficheproduction
 * \brief       Gestion des actions de formulaire pour FicheProduction
 */

// Prevent direct access
if (!defined('DOL_VERSION')) {
    print "Error: This module requires Dolibarr framework.\n";
    exit;
}

/**
 * Handle form actions for FicheProduction
 * 
 * @param string   $action  Action to perform
 * @param Commande $object  Order object
 * @param User     $user    Current user
 * @param Translate $langs  Language object
 */
function handleFicheProductionActions($action, $object, $user, $langs) 
{
    switch ($action) {
        case 'update_ref_chantierfp':
            handleUpdateRefChantier($object, $user, $langs);
            break;
            
        case 'update_commentaires_fp':
            handleUpdateCommentaires($object, $user, $langs);
            break;
    }
}

/**
 * Handle reference chantier update
 * 
 * @param Commande $object Order object
 * @param User     $user   Current user
 * @param Translate $langs Language object
 */
function handleUpdateRefChantier($object, $user, $langs) 
{
    // Récupération des données du formulaire avec sécurité
    $ref_chantierfp = GETPOST('ref_chantierfp', 'alpha');
    
    // Vérification que les extrafields sont chargés
    if (!isset($object->array_options) || !is_array($object->array_options)) {
        $object->array_options = array();
    }
    
    // Mise à jour de l'extrafield
    $object->array_options['options_ref_chantierfp'] = $ref_chantierfp;
    
    // Sauvegarde avec le user courant
    $result = $object->insertExtraFields('', $user);
    
    if ($result < 0) {
        // Affichage des erreurs
        setEventMessages($object->error, $object->errors, 'errors');
    } else {
        // Message de succès
        setEventMessages($langs->trans("RecordSaved"), null);
        // Redirection pour éviter les soumissions multiples
        header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
        exit;
    }
}

/**
 * Handle comments update
 * 
 * @param Commande $object Order object
 * @param User     $user   Current user
 * @param Translate $langs Language object
 */
function handleUpdateCommentaires($object, $user, $langs) 
{
    // Récupération des données du formulaire avec sécurité
    $commentaires_fp = GETPOST('commentaires_fp', 'restricthtml');
    
    // Vérification que les extrafields sont chargés
    if (!isset($object->array_options) || !is_array($object->array_options)) {
        $object->array_options = array();
    }
    
    // Mise à jour de l'extrafield
    $object->array_options['options_commentaires_fp'] = $commentaires_fp;
    
    // Sauvegarde avec le user courant
    $result = $object->insertExtraFields('', $user);
    
    if ($result < 0) {
        // Affichage des erreurs
        setEventMessages($object->error, $object->errors, 'errors');
    } else {
        // Message de succès
        setEventMessages($langs->trans("RecordSaved"), null);
        // Redirection pour éviter les soumissions multiples
        header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
        exit;
    }
}
?>