<?php
/**
 * Page principale du module Fiche de Production
 */

// Pour le débogage uniquement - à commenter en production
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// Load Dolibarr environment
$res = 0;
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

// Déclaration des variables globales
global $db, $langs, $user, $conf;

// Traitement des actions AJAX
if (isset($_GET['action']) && $_GET['action'] == 'load_data') {
    header('Content-Type: application/json');
    
    // Vérification du token CSRF
    $token = GETPOST('token', 'alpha');
    if (!$token || $token != $_SESSION['token']) {
        echo json_encode(array('success' => false, 'error' => 'Token de sécurité invalide'));
        exit;
    }
    
    // Récupération des paramètres
    $productId = GETPOST('productId', 'int');
    $fk_commande = GETPOST('fk_commande', 'int');
    
    // Vérification minimale
    if ($productId <= 0 || $fk_commande <= 0) {
        echo json_encode(array('success' => false, 'error' => 'Paramètres invalides'));
        exit;
    }

    try {
        // Requête SQL simplifiée
        $sql = "SELECT colisage_data FROM ".MAIN_DB_PREFIX."ficheproduction ";
        $sql.= "WHERE fk_commande = ".$fk_commande." AND fk_product = ".$productId;
        
        $resql = $db->query($sql);
        
        if (!$resql) {
            echo json_encode(array('success' => false, 'error' => 'Erreur SQL : ' . $db->lasterror()));
            exit;
        }
        
        // Récupération des données
        if ($db->num_rows($resql) > 0) {
            $obj = $db->fetch_object($resql);
            $data = $obj->colisage_data;
            $db->free($resql);
            echo json_encode(array('success' => true, 'data' => $data));
        } else {
            echo json_encode(array('success' => false, 'data' => null));
        }
    } catch (Exception $e) {
        echo json_encode(array('success' => false, 'error' => 'Exception : ' . $e->getMessage()));
    }
    
    exit;
}

// Action pour traiter la sauvegarde des données de colisage via AJAX
if (isset($_POST['action']) && $_POST['action'] == 'save') {
    header('Content-Type: application/json');
    
    // Vérification du token CSRF
    $token = GETPOST('token', 'alpha');
    if (!$token || $token != $_SESSION['token']) {
        echo json_encode(array('success' => false, 'error' => 'Token de sécurité invalide'));
        exit;
    }
    
    // Récupération des paramètres
    $productId = GETPOST('productId', 'int');
    $fk_commande = GETPOST('fk_commande', 'int');
    $colisage_data = GETPOST('colisage_data', 'none'); // Utiliser 'none' pour éviter le filtrage
    
    // Vérification minimale
    if ($productId <= 0 || $fk_commande <= 0 || empty($colisage_data)) {
        echo json_encode(array('success' => false, 'error' => 'Paramètres invalides'));
        exit;
    }

    try {
        // Vérifier si une entrée existe déjà
        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."ficheproduction ";
        $sql.= "WHERE fk_commande = ".$fk_commande." AND fk_product = ".$productId;
        
        $resql = $db->query($sql);
        
        if (!$resql) {
            echo json_encode(array('success' => false, 'error' => 'Erreur SQL (check) : ' . $db->lasterror()));
            exit;
        }
        
        if ($db->num_rows($resql) > 0) {
            // Mise à jour
            $obj = $db->fetch_object($resql);
            $db->free($resql);
            
            $sql = "UPDATE ".MAIN_DB_PREFIX."ficheproduction SET ";
            $sql.= "colisage_data = '".$db->escape($colisage_data)."', ";
            $sql.= "fk_user_modif = ".$user->id.", ";
            $sql.= "tms = '".$db->idate(dol_now())."' ";
            $sql.= "WHERE rowid = ".$obj->rowid;
            
            $result = $db->query($sql);
            
            if (!$result) {
                echo json_encode(array('success' => false, 'error' => 'Erreur SQL (update) : ' . $db->lasterror()));
                exit;
            }
        } else {
            // Insertion - génération d'une référence
            $refprefix = 'FP';
            $ref = $refprefix . $fk_commande . '-' . $productId . '-' . date('Ymd');
            
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."ficheproduction ";
            $sql.= "(ref, fk_soc, fk_commande, fk_product, colisage_data, date_creation, fk_user_creat, status) ";
            $sql.= "VALUES (";
            $sql.= "'".$db->escape($ref)."', ";
            
            // Récupérer fk_soc depuis la commande
            $sqlsoc = "SELECT fk_soc FROM ".MAIN_DB_PREFIX."commande WHERE rowid = ".$fk_commande;
            $ressqlsoc = $db->query($sqlsoc);
            if ($ressqlsoc && $db->num_rows($ressqlsoc) > 0) {
                $objsoc = $db->fetch_object($ressqlsoc);
                $sql.= $objsoc->fk_soc.", ";
                $db->free($ressqlsoc);
            } else {
                $sql.= "0, "; // Valeur par défaut si la société n'est pas trouvée
            }
            
            $sql.= $fk_commande.", ";
            $sql.= $productId.", ";
            $sql.= "'".$db->escape($colisage_data)."', ";
            $sql.= "'".$db->idate(dol_now())."', ";
            $sql.= $user->id.", ";
            $sql.= "1)"; // status = 1 (actif)
            
            $result = $db->query($sql);
            
            if (!$result) {
                echo json_encode(array('success' => false, 'error' => 'Erreur SQL (insert) : ' . $db->lasterror()));
                exit;
            }
        }
        
        echo json_encode(array('success' => true));
        
    } catch (Exception $e) {
        echo json_encode(array('success' => false, 'error' => 'Exception : ' . $e->getMessage()));
    }
    
    exit;
}

// À partir d'ici, c'est la partie affichage normale de la page

require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/order.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

// Load translation files
$langs->loadLangs(array("orders", "companies", "products", "ficheproduction@ficheproduction"));

// Get parameters
$id = GETPOST('id', 'int');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');

// Initialize technical objects
$object = new Commande($db);
$form = new Form($db);
$formmail = new FormMail($db);
$extrafields = new ExtraFields($db);

// Load object
if ($id > 0) {
    $result = $object->fetch($id);
    if ($result <= 0) {
        dol_print_error($db, $object->error);
        exit;
    }
    
    $result = $object->fetch_thirdparty();
    if ($result < 0) {
        dol_print_error($db, $object->error);
        exit;
    }
    
    // Chargement des lignes de commande
    $object->fetch_lines();
    
    // Chargement des extrafields
    $object->fetch_optionals();
}

// Security check using the standard Dolibarr function
$result = restrictedArea($user, 'commande', $id);

// Set userCanEdit - check if user has right to edit orders
$userCanEdit = $user->rights->commande->creer ?? false;

/*
 * ACTIONS
 */

// Action pour mettre à jour la référence chantier
if ($action == 'update_ref_chantierfp') {
    // Récupération des données du formulaire avec sécurité
    $ref_chantierfp = GETPOST('ref_chantierfp', 'alpha');
    
    // Vérification que les extrafields sont chargés
    if (!isset($object->array_options) || !is_array($object->array_options)) {
        $object->array_options = array();
    }
    
    // Mise à jour de l'extrafield
    $object->array_options['options_ref_chantierfp'] = $ref_chantierfp;
    
    // Debug
    dol_syslog("ficheproduction.php: update_ref_chantierfp - valeur: ".$ref_chantierfp, LOG_DEBUG);
    
    // Sauvegarde avec le user courant
    $result = $object->insertExtraFields('', $user);
    
    if ($result < 0) {
        // Affichage des erreurs
        setEventMessages($object->error, $object->errors, 'errors');
        dol_syslog("ficheproduction.php: Error saving extrafields: ".$object->error, LOG_ERR);
    } else {
        // Message de succès
        setEventMessages($langs->trans("RecordSaved"), null);
        // Redirection pour éviter les soumissions multiples
        header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
        exit;
    }
}

// Action pour mettre à jour les commentaires
if ($action == 'update_commentaires_fp') {
    // Récupération des données du formulaire avec sécurité
    $commentaires_fp = GETPOST('commentaires_fp', 'restricthtml');
    
    // Vérification que les extrafields sont chargés
    if (!isset($object->array_options) || !is_array($object->array_options)) {
        $object->array_options = array();
    }
    
    // Mise à jour de l'extrafield
    $object->array_options['options_commentaires_fp'] = $commentaires_fp;
    
    // Debug
    dol_syslog("ficheproduction.php: update_commentaires_fp - longueur: ".strlen($commentaires_fp), LOG_DEBUG);
    
    // Sauvegarde avec le user courant
    $result = $object->insertExtraFields('', $user);
    
    if ($result < 0) {
        // Affichage des erreurs
        setEventMessages($object->error, $object->errors, 'errors');
        dol_syslog("ficheproduction.php: Error saving extrafields: ".$object->error, LOG_ERR);
    } else {
        // Message de succès
        setEventMessages($langs->trans("RecordSaved"), null);
        // Redirection pour éviter les soumissions multiples
        header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
        exit;
    }
}

/*
 * VIEW
 */

// Inclusion du CSS et JS
$head_html = '
<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/custom/ficheproduction/css/ficheproduction.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community@30.2.1/styles/ag-grid.css" type="text/css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community@30.2.1/styles/ag-theme-alpine.css" type="text/css" />
<script src="https://cdn.jsdelivr.net/npm/ag-grid-community@30.2.1/dist/ag-grid-community.min.js"></script>
<script src="'.DOL_URL_ROOT.'/custom/ficheproduction/js/ficheproduction.js"></script>
<script>window.DEBUG_MODE = ' . ($conf->global->MAIN_DEBUG_ENABLED ? 'true' : 'false') . ';</script>
';

// Page header & tabs avec les inclusions CSS et JS
$title = $langs->trans("Order").' '.$object->ref.' - '.$langs->trans("FicheProduction");
llxHeader($head_html, $title);

// Affichage des onglets
$head = commande_prepare_head($object);
print dol_get_fiche_head($head, 'ficheproduction', $langs->trans("CustomerOrder"), -1, 'order');

// Affichage des informations de la commande dans une card
print '<div class="fichecenter">';

// Layout en deux colonnes
print '<div style="display:flex; flex-wrap:wrap; gap:20px;">';

// Colonne gauche - Tableau récapitulatif
print '<div style="flex:1; min-width:300px;">';
print '<div class="underbanner clearboth"></div>';
print '<table class="border centpercent tableforfield">';

// Référence commande
print '<tr><td class="titlefield">'.$langs->trans("OrderReference").':</td><td>'.$object->ref.'</td></tr>';

// Client
print '<tr><td>'.$langs->trans("CustomerName").':</td><td>'.$object->thirdparty->getNomUrl(1).'</td></tr>';

// Référence chantier (extrafield ref_chantierfp)
$ref_chantier = !empty($object->array_options['options_ref_chantier']) ? $object->array_options['options_ref_chantier'] : '';
$ref_chantierfp = !empty($object->array_options['options_ref_chantierfp']) ? $object->array_options['options_ref_chantierfp'] : $ref_chantier;

print '<tr>';
print '<td>'.$langs->trans("ProjectReference").':</td>';
print '<td>';

if ($action == 'edit_ref_chantierfp') {
    // Formulaire d'édition
    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="update_ref_chantierfp">';
    print '<input type="text" name="ref_chantierfp" size="40" value="'.$ref_chantierfp.'">';
    print ' <input type="submit" class="button" value="'.$langs->trans("Save").'">';
    print ' <a class="button button-cancel" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">'.$langs->trans("Cancel").'</a>';
    print '</form>';
} else {
    // Affichage normal avec icône d'édition
    print $ref_chantierfp;
    if ($userCanEdit) {
        print ' <a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit_ref_chantierfp">'.img_edit($langs->trans("Edit")).'</a>';
    }
}

print '</td>';
print '</tr>';

// Commentaires (extrafield commentaires_fp)
$commentaires_fp = !empty($object->array_options['options_commentaires_fp']) ? $object->array_options['options_commentaires_fp'] : '';

print '<tr>';
print '<td>'.$langs->trans("Comments").':</td>';
print '<td>';

if ($action == 'edit_commentaires_fp') {
    // Formulaire d'édition avec éditeur WYSIWYG
    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="update_commentaires_fp">';
    
    // Utilisation de l'éditeur Dolibarr
    $doleditor = new DolEditor('commentaires_fp', $commentaires_fp, '', 200, 'dolibarr_notes', '', false, true, true, ROWS_5, '90%');
    print $doleditor->Create(1);
    
    print '<br><input type="submit" class="button" value="'.$langs->trans("Save").'">';
    print ' <a class="button button-cancel" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">'.$langs->trans("Cancel").'</a>';
    print '</form>';
} else {
    // Affichage normal avec icône d'édition
    print $commentaires_fp; // Le contenu HTML sera affiché correctement
    if ($userCanEdit) {
        print ' <a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit_commentaires_fp">'.img_edit($langs->trans("Edit")).'</a>';
    }
}

print '</td>';
print '</tr>';

// Total colis (sera calculé dynamiquement via JavaScript)
print '<tr>';
print '<td>'.$langs->trans("TotalPackages").':</td>';
print '<td><span id="total-packages">0</span></td>';
print '</tr>';

// Poids total (extrafield poids_total)
$poids_total = !empty($object->array_options['options_poids_total']) ? $object->array_options['options_poids_total'] : 0;
print '<tr>';
print '<td>'.$langs->trans("TotalWeight").':</td>';
print '<td>'.$poids_total.' kg</td>';
print '</tr>';

print '</table>';
print '</div>'; // Fermeture div colonne gauche

// Colonne droite - Adresse de livraison
print '<div style="flex:0 0 300px;">';
print '<div class="delivery-info-box" style="padding:15px; background:#f9f9f9; border:1px solid #ddd; border-radius:4px; height:100%;">';
print '<h4>' . $langs->trans("DeliveryInformation") . '</h4>';

// Récupération des infos de livraison
$contact_livraison = "";
$address_livraison = "";
$phone_livraison = "";
$email_livraison = "";
$note_public_livraison = "";

// Récupérer les contacts associés à la commande
$contacts = $object->liste_contact(-1, 'external', 0, 'SHIPPING');
if (is_array($contacts) && count($contacts) > 0) {
    foreach ($contacts as $contact) {
        // Récupérer les détails du contact de livraison
        $contactstatic = new Contact($db);
        if ($contactstatic->fetch($contact['id']) > 0) {
            $contact_livraison = $contactstatic->getFullName($langs);
            $address_livraison = $contactstatic->address;
            $address_livraison .= "\n" . $contactstatic->zip . " " . $contactstatic->town;
            $address_livraison .= !empty($contactstatic->country) ? "\n" . $contactstatic->country : "";
            
            // Récupérer tous les numéros de téléphone disponibles
            $phone_array = array();
            if (!empty($contactstatic->phone_pro)) $phone_array[] = $langs->trans("Pro") . ": " . $contactstatic->phone_pro;
            if (!empty($contactstatic->phone_perso)) $phone_array[] = $langs->trans("Personal") . ": " . $contactstatic->phone_perso;
            if (!empty($contactstatic->phone_mobile)) $phone_array[] = $langs->trans("Mobile") . ": " . $contactstatic->phone_mobile;
            
            $phone_livraison = implode(" / ", $phone_array);
            $email_livraison = $contactstatic->email;
            
            // Récupérer la note publique du contact
            $note_public_livraison = $contactstatic->note_public;
        }
        break; // On ne prend que le premier contact de livraison
    }
}

// Si pas de contact spécifique, on prend l'adresse de livraison de la commande
if (empty($contact_livraison) && !empty($object->array_options['options_adresse_livraison'])) {
    $address_livraison = $object->array_options['options_adresse_livraison'];
}

// Si toujours pas d'adresse, on prend celle du client
if (empty($address_livraison)) {
    $contact_livraison = $object->thirdparty->name;
    $address_livraison = $object->thirdparty->address;
    $address_livraison .= "\n" . $object->thirdparty->zip . " " . $object->thirdparty->town;
    $address_livraison .= !empty($object->thirdparty->country) ? "\n" . $object->thirdparty->country : "";
    
    // Pour la société, on récupère les différents téléphones disponibles
    $phone_array = array();
    if (!empty($object->thirdparty->phone)) $phone_array[] = $langs->trans("Pro") . ": " . $object->thirdparty->phone;
    if (!empty($object->thirdparty->fax)) $phone_array[] = $langs->trans("Fax") . ": " . $object->thirdparty->fax;
    
    $phone_livraison = implode(" / ", $phone_array);
    $email_livraison = $object->thirdparty->email;
    
    // Récupérer la note publique de la société
    $note_public_livraison = $object->thirdparty->note_public;
}

// Affichage des informations
print '<p><strong>' . $langs->trans("Contact") . ':</strong> ' . $contact_livraison . '</p>';
print '<p><strong>' . $langs->trans("Address") . ':</strong> ' . nl2br($address_livraison) . '</p>';
if (!empty($phone_livraison)) {
    print '<p><strong>' . $langs->trans("Phone") . ':</strong> ' . $phone_livraison . '</p>';
}
if (!empty($email_livraison)) {
    print '<p><strong>' . $langs->trans("Email") . ':</strong> ' . $email_livraison . '</p>';
}
if (!empty($note_public_livraison)) {
    print '<p><strong>' . $langs->trans("NotePublic") . ':</strong> ' . nl2br($note_public_livraison) . '</p>';
}

print '</div>'; // Fin box
print '</div>'; // Fermeture div colonne droite

print '</div>'; // Fermeture div layout flexbox
print '</div>'; // Fermeture de la div fichecenter

// Champ caché pour l'ID de commande (utilisé par JavaScript)
print '<input type="hidden" id="fk_commande" value="'.$object->id.'">';

// Création du token CSRF pour sécuriser les requêtes AJAX
print '<input type="hidden" id="token" value="'.newToken().'">';

// Section des produits
print '<div class="ficheproduction-container">';

// Regrouper les produits par libellé et couleur
$products = array();
$product_colors = array();
$productObj = new Product($db);

if (is_array($object->lines) && count($object->lines) > 0) {
    foreach ($object->lines as $line) {
        // Ne prendre que les produits (pas les services)
        if ($line->product_type == 0 && $line->fk_product > 0) {
            // Récupérer les informations du produit pour le libellé
            $productObj->fetch($line->fk_product);
            
            // Récupérer les extrafields de la ligne
            $line->fetch_optionals();
            
            // Récupérer tous les extrafields de la ligne avec gestion des valeurs nulles
            $color = isset($line->array_options['options_couleur']) && $line->array_options['options_couleur'] !== '' ? $line->array_options['options_couleur'] : 'Sans couleur';
            $nombre = isset($line->array_options['options_nombre']) && $line->array_options['options_nombre'] !== '' ? $line->array_options['options_nombre'] : '0';
            $longueur = isset($line->array_options['options_longueur']) && $line->array_options['options_longueur'] !== '' ? $line->array_options['options_longueur'] : '0';
            $largeur = isset($line->array_options['options_largeur']) && $line->array_options['options_largeur'] !== '' ? $line->array_options['options_largeur'] : '0';
            $ref_ligne = isset($line->array_options['options_ref_ligne']) && $line->array_options['options_ref_ligne'] !== '' ? $line->array_options['options_ref_ligne'] : '';
            
            // Utiliser la quantité de la ligne de commande
            $quantite = $line->qty;
            
            // Clé pour regrouper les produits (libellé + couleur)
            $key = $productObj->label . '|' . $color;
            
            // Stocker les informations du produit
            if (!isset($products[$key])) {
                $products[$key] = array();
                $product_colors[$key] = $color;
            }
            
            $products[$key][] = array(
                'id' => $line->fk_product,
                'label' => $productObj->label,
                'ref' => $productObj->ref,
                'desc' => $productObj->description,
                'color' => $color,
                'nombre' => $nombre,
                'longueur' => $longueur,
                'largeur' => $largeur,
                'quantite' => $quantite,
                'ref_ligne' => $ref_ligne,
                'qty' => $line->qty,
                'price' => $line->price
            );
        }
    }
}

// Afficher les groupes de produits
if (count($products) > 0) {
    foreach ($products as $key => $group) {
        $label_parts = explode('|', $key);
        $label = $label_parts[0];
        $color = $product_colors[$key];
        
        print '<div class="product-group">';
        print '<h3>' . $label . ' - ' . $color . '</h3>';
        
        print '<div class="product-tables-container">';
        
        /// Tableau des caractéristiques à gauche - VERSION CORRIGÉE
        print '<div class="product-table-left">';
        print '<h4>Quantités Commandées</h4>';
        print '<table class="ficheproduction-product-table">';
        print '<thead>';
        print '<tr>';
        print '<th>' . $langs->trans("NumberColumn") . '</th>';
        print '<th>' . $langs->trans("LengthColumn") . '</th>';
        print '<th>' . $langs->trans("WidthColumn") . '</th>';
        print '<th>' . $langs->trans("QuantityColumn") . '</th>';
        print '<th>' . $langs->trans("RefColumn") . '</th>';
        print '</tr>';
        print '</thead>';
        print '<tbody>';

        $group_total_quantity = 0; // Initialiser le total à zéro

        foreach ($group as $product) {
            // Formater les valeurs numériques pour l'affichage
            $nombre_display = is_numeric($product['nombre']) ? number_format((float)$product['nombre'], 0, '.', ' ') : $product['nombre'];
            $longueur_display = is_numeric($product['longueur']) ? number_format((float)$product['longueur'], 0, '.', ' ') : $product['longueur'];
            $largeur_display = is_numeric($product['largeur']) ? number_format((float)$product['largeur'], 0, '.', ' ') : $product['largeur'];
            $quantite_display = is_numeric($product['quantite']) ? number_format((float)$product['quantite'], 3, '.', ' ') : $product['quantite'];
            
            // Ajouter au total de la quantité pour ce groupe - ASSUREZ-VOUS QUE C'EST BIEN UN NOMBRE
            $product_quantity = is_numeric($product['quantite']) ? (float)$product['quantite'] : 0;
            $group_total_quantity += $product_quantity;
            
            print '<tr>';
            print '<td>' . $nombre_display . '</td>';
            print '<td>' . $longueur_display . '</td>';
            print '<td>' . $largeur_display . '</td>';
            print '<td>' . $quantite_display . '</td>';
            print '<td>' . $product['ref_ligne'] . '</td>';
            print '</tr>';
        }

        // Ajouter une ligne de total
        print '<tr style="font-weight: bold; background-color: #f5f5f5;">';
        print '<td colspan="3" style="text-align: right;">Total:</td>';
        print '<td>' . number_format($group_total_quantity, 3, '.', ' ') . '</td>';
        print '<td></td>';
        print '</tr>';

        print '</tbody>';
        print '</table>';
        
        // Champ caché pour la quantité totale (utilisé par JavaScript)
        print '<input type="hidden" id="product-total-quantity-' . $group[0]['id'] . '" value="' . $group_total_quantity . '">';
        
        // Champ pour comparer les quantités
        print '<div id="quantity-compare-' . $group[0]['id'] . '" class="quantity-compare quantity-match">Quantité colisage: 0.000</div>';
        
        print '</div>'; // Fin product-table-left
        
        // Container pour le AG Grid à droite
        print '<div class="product-table-right">';
        print '<h4>' . $langs->trans("PackagingPlan") . '</h4>';
        
        // Pour chaque produit du groupe, créer un input caché avec sa largeur et sa référence
        foreach ($group as $product) {
            print '<input type="hidden" id="product-width-' . $product['id'] . '" value="' . $product['largeur'] . '">';
            print '<input type="hidden" id="product-ref-ligne-' . $product['id'] . '" value="' . $product['ref_ligne'] . '">';
            print '<input type="hidden" id="product-packages-' . $product['id'] . '" value="0">';
        }
        
        // Container pour le grid
        print '<div id="spreadsheet-' . $group[0]['id'] . '" class="ficheproduction-jspreadsheet-container"></div>';
        
        // Boutons d'action pour le grid
        print '<div id="buttons-' . $group[0]['id'] . '" class="ficheproduction-buttons"></div>';
        
        print '</div>'; // Fin product-table-right
        
        print '</div>'; // Fin product-tables-container
        print '</div>'; // Fin product-group
    }
} else {
    print '<div class="opacitymedium">' . $langs->trans("NoProducts") . '</div>';
}

// Ajouter la section de signature et vérification à la fin du document
print '<div class="colisage-signature" style="margin-top:30px; padding-top:20px; border-top:1px solid #ddd;">';
print '<h3>' . $langs->trans("FinalChecks") . '</h3>';
print '<table class="colisage-check-table" style="width:100%; border-collapse:collapse;">';
print '<tr>';

// Colonne de gauche - COLISAGE FINAL
print '<td style="width:50%; padding:15px; vertical-align:top; border:1px solid #ddd;">';
print '<p><strong>' . $langs->trans("FinalPackaging") . '</strong></p>';
print '<p style="margin:10px 0;">______' . $langs->trans("Pallets") . ' ' . $langs->trans("Being") . ' ______' . $langs->trans("Packages") . '</p>';
print '<p style="margin:10px 0;">______' . $langs->trans("Bundles") . ' ' . $langs->trans("Being") . ' ______' . $langs->trans("Packages") . '</p>';
print '<p style="margin:10px 0;">      ' . $langs->trans("BulkPackages") . ' ______' . $langs->trans("Packages") . '</p>';
print '<p style="margin:15px 0;"><strong>' . $langs->trans("TotalNumberOfPackages") . '</strong> : ______' . $langs->trans("Packages") . '</p>';
print '</td>';

// Colonne de droite - Vérifications et signatures
print '<td style="width:50%; padding:15px; vertical-align:top; border:1px solid #ddd;">';
print '<p>' . $langs->trans("ProductionSheetVerificationBy") . ' : __________</p>';
print '<p style="height:20px;"></p>'; // Espace pour signature
print '<p>' . $langs->trans("FinalCounting") . ' ' . $langs->trans("AndReturnDateBy") . ' : __________</p>';
print '<p style="height:20px;"></p>'; // Espace pour signature
print '<p>' . $langs->trans("CoilsIDUsed") . ' : __________</p>';
print '<p style="height:20px;"></p>'; // Espace pour signature
print '</td>';

print '</tr>';
print '</table>';
print '</div>';

print '</div>'; // Fin ficheproduction-container

// Bouton d'impression
print '<div class="tabsAction">';
print '<a class="butAction" href="javascript:preparePrint();">' . $langs->trans("PrintButton") . '</a>';
print '</div>';

// Script pour la fonction d'impression
print '<script>
function preparePrint() {
    // Sauvegarde l\'état actuel de la page
    var originalTitle = document.title;
    
    // Modifie le titre pour l\'impression
    document.title = "' . $langs->trans("FicheProduction") . ' - ' . $object->ref . '";
    
    // Lance l\'impression
    window.print();
    
    // Restaure le titre original après l\'impression
    setTimeout(function() {
        document.title = originalTitle;
    }, 1000);
}
</script>';

// Fermeture des onglets et pied de page
print dol_get_fiche_end();
llxFooter();
$db->close();