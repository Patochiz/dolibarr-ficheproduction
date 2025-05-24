<?php
/* Copyright (C) 2025 SuperAdmin
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file        ficheproduction.php
 * \ingroup     ficheproduction
 * \brief       Fiche de production page
 */

//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB', '1');	// Do not create database handler $db
//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER', '1');	// Do not load object $user
//if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC', '1');	// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN', '1');	// Do not load object $langs
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION', '1');	// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION', '1');	// Do not check injection attack on POST parameters
//if (! defined('NOCSRFCHECK'))              define('NOCSRFCHECK', '1');	// Do not check CSRF attack (test on referer + on token).
//if (! defined('NOTOKENRENEWAL'))           define('NOTOKENRENEWAL', '1');	// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
//if (! defined('NOSTYLECHECK'))             define('NOSTYLECHECK', '1');	// Do not check style html tag into posted data
//if (! defined('NOREQUIREMENU'))            define('NOREQUIREMENU', '1');	// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))            define('NOREQUIREHTML', '1');	// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))            define('NOREQUIREAJAX', '1');       // Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))                  define("NOLOGIN", '1');	// If this page is public (can be called outside logged session). This include the NOIPCHECK too.
//if (! defined('NOIPCHECK'))                define('NOIPCHECK', '1');	// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined("MAIN_LANG_DEFAULT"))        define('MAIN_LANG_DEFAULT', 'auto');	// Force lang to a particular value
//if (! defined("MAIN_AUTHENTICATION_MODE")) define('MAIN_AUTHENTICATION_MODE', 'aloginmodule');	// Force authentication handler
//if (! defined("NOREDIRECTBYMAINTOLOGIN"))  define('NOREDIRECTBYMAINTOLOGIN', 1);	// The main.inc.php does not make a redirect if not logged, instead show simple error message
//if (! defined("FORCECSP"))                 define('FORCECSP', 'none');	// Disable all Content Security Policies
//if (! defined('CSRFCHECK_WITH_TOKEN'))     define('CSRFCHECK_WITH_TOKEN', '1');	// Force use of CSRF protection with tokens even for GET
//if (! defined('NOBROWSERNOTIF'))     		define('NOBROWSERNOTIF', '1');	// Disable browser notification
//if (! defined('NOSESSION'))     		    define('NOSESSION', '1');	// Disable session

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

dol_include_once('/ficheproduction/class/ficheproductionmanager.class.php');
dol_include_once('/ficheproduction/class/ficheproductionsession.class.php');
dol_include_once('/ficheproduction/class/ficheproductioncolis.class.php');
dol_include_once('/ficheproduction/lib/ficheproduction.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("ficheproduction@ficheproduction", "orders", "products"));

// Get parameters
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'ficheproduction'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
$lineid = GETPOST('lineid', 'int');

// Initialize technical objects
$object = new Commande($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->ficheproduction->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('ficheproductioncard', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch extrafields
$extrafields->fetch_name_optionals_label($object->table_element);

// Initialize array of search criterias
$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize objects
$object = new Commande($db);

// Security check - Protection if external user
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

$result = restrictedArea($user, 'commande', $id);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once

// Additional security checks
if ($object->id > 0) {
	if ($object->statut == Commande::STATUS_DRAFT) {
		accessforbidden('', 0, 0, 1);
	}
}

// Initialize FicheProductionManager
$manager = new FicheProductionManager($db);

// Handle AJAX requests
if ($action == 'save_colisage' && GETPOST('ajax', 'alpha')) {
	$colisData = json_decode(GETPOST('colisData', 'none'), true);
	
	if ($colisData) {
		$result = $manager->saveColisageData($object->id, $object->socid, $colisData, $user);
		
		header('Content-Type: application/json');
		echo json_encode($result);
		exit;
	} else {
		header('Content-Type: application/json');
		echo json_encode(array('success' => false, 'message' => 'Données de colisage invalides'));
		exit;
	}
}

if ($action == 'load_colisage' && GETPOST('ajax', 'alpha')) {
	$result = $manager->loadColisageData($object->id);
	
	header('Content-Type: application/json');
	echo json_encode($result);
	exit;
}

if ($action == 'get_order_products' && GETPOST('ajax', 'alpha')) {
	$products = array();
	
	if ($object->id > 0) {
		$object->fetch_lines();
		
		foreach ($object->lines as $line) {
			if ($line->fk_product > 0) {
				$product = new Product($db);
				$product->fetch($line->fk_product);
				
				$products[] = array(
					'id' => $product->id,
					'ref' => $product->ref,
					'label' => $product->label,
					'weight' => $product->weight ?: 1,
					'length' => $product->length ?: 0,
					'width' => $product->width ?: 0,
					'height' => $product->height ?: 0,
					'color' => $product->customcode ?: 'Naturel',
					'total' => $line->qty,
					'used' => 0 // Will be calculated by JavaScript
				);
			}
		}
	}
	
	header('Content-Type: application/json');
	echo json_encode($products);
	exit;
}

/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$error = 0;

	$permissiontoadd = $user->rights->commande->creer; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
	$permissiontodelete = $user->rights->commande->supprimer || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
	$permissionnote = $user->rights->commande->creer; // Used by the include of actions_setnotes.inc.php
	$permissiondellink = $user->rights->commande->creer; // Used by the include of actions_dellink.inc.php
	$upload_dir = $conf->commande->multidir_output[isset($object->entity) ? $object->entity : 1];

	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	//include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	// Actions when linking object to another
	//include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';

	// Actions when printing a doc from card
	//include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Action to move up and down lines of object
	//include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';

	// Action to build doc
	//include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

	if ($action == 'set_thirdparty' && $permissiontoadd) {
		$object->setValueFrom('fk_soc', GETPOST('fk_soc', 'int'), '', '', 'date', '', $user, $triggermodname);
	}
	if ($action == 'classin' && $permissiontoadd) {
		$object->setProject(GETPOST('projectid', 'int'));
	}

	// Actions to send emails
	$triggersendname = 'FICHEPRODUCTION_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_FICHEPRODUCTION_TO';
	$trackid = 'ficheproduction'.$object->id;
	//include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
}

/*
 * View
 *
 * Put here all code to build page
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

$title = $langs->trans("FicheProduction").' - '.$object->ref;
$help_url = '';
llxHeader('', $title, $help_url, '', 0, 0, array('/custom/ficheproduction/css/ficheproduction.css'));

if (empty($conf->commande->enabled)) {
	accessforbidden('Module Commandes not enabled');
}

if ($object->id > 0) {
	$head = commande_prepare_head($object);

	print dol_get_fiche_head($head, 'ficheproduction', $langs->trans("CustomerOrder"), -1, $object->picto);

	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/commande/list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	// Ref customer
	$morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', 0, 1);
	$morehtmlref .= $form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', null, null, '', 1);
	// Thirdparty
	$morehtmlref .= '<br>'.$langs->trans('ThirdParty').' : '.$object->thirdparty->getNomUrl(1, 'customer');
	// Project
	if (isModEnabled('project')) {
		$langs->load("projects");
		$morehtmlref .= '<br>'.$langs->trans('Project').' ';
		if ($permissiontoadd) {
			//if ($action != 'classify') $morehtmlref.='<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> ';
			$morehtmlref .= ' : ';
			if ($action == 'classify') {
				//$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
				$morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
				$morehtmlref .= '<input type="hidden" name="action" value="classin">';
				$morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
				$morehtmlref .= $formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
				$morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
				$morehtmlref .= '</form>';
			} else {
				$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1, '', 'maxwidth300');
			}
		} else {
			if (!empty($object->fk_project)) {
				$proj = new Project($db);
				$proj->fetch($object->fk_project);
				$morehtmlref .= ': '.$proj->getNomUrl();
			} else {
				$morehtmlref .= '';
			}
		}
	}
	$morehtmlref .= '</div>';

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	// Common attributes
	//$keyforbreak='fieldkeytoswithrighttitle';  // We change column just before this field
	//unset($object->fields['fk_project']);				// Hide field already shown in banner
	//unset($object->fields['fk_soc']);					// Hide field already shown in banner
	//include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	// Other attributes. Fields from hook formObjectOptions and Extrafields.
	//include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();

	// Interface principal pour la fiche de production
	?>
	
	<!-- Interface de Fiche de Production -->
	<div class="container-fluid ficheproduction-container">
		<div class="header">
			<h1><?php echo $langs->trans('ProductionSheet'); ?> - <?php echo $object->ref; ?></h1>
			<div class="order-info-summary">
				<div class="summary-card">
					<h3><?php echo $langs->trans('OrderSummary'); ?></h3>
					<div class="summary-details">
						<div class="summary-item">
							<strong><?php echo $langs->trans('Client'); ?>:</strong>
							<span><?php echo $object->thirdparty->name; ?></span>
						</div>
						<div class="summary-item">
							<strong><?php echo $langs->trans('RefCustomer'); ?>:</strong>
							<span><?php echo $object->ref_client; ?></span>
						</div>
						<div class="summary-item">
							<strong><?php echo $langs->trans('OrderDate'); ?>:</strong>
							<span><?php echo dol_print_date($object->date, 'day'); ?></span>
						</div>
						<div class="summary-item">
							<strong><?php echo $langs->trans('TotalProducts'); ?>:</strong>
							<span id="orderTotalProducts">-</span>
						</div>
						<div class="summary-item">
							<strong><?php echo $langs->trans('TotalWeight'); ?>:</strong>
							<span id="orderTotalWeight">- kg</span>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="controls">
			<div class="search-controls">
				<input type="text" id="productSearch" placeholder="<?php echo $langs->trans('SearchProducts'); ?>...">
				<select id="productGroupSelect">
					<option value="all"><?php echo $langs->trans('AllProducts'); ?></option>
					<option value="assigned"><?php echo $langs->trans('AssignedProducts'); ?></option>
					<option value="unassigned"><?php echo $langs->trans('UnassignedProducts'); ?></option>
				</select>
				<select id="sortSelect">
					<option value="ref"><?php echo $langs->trans('SortByRef'); ?></option>
					<option value="label"><?php echo $langs->trans('SortByLabel'); ?></option>
					<option value="weight"><?php echo $langs->trans('SortByWeight'); ?></option>
				</select>
			</div>
			<div class="action-controls">
				<button id="addNewColisBtn" class="btn btn-primary"><?php echo $langs->trans('NewPackage'); ?></button>
				<button id="addNewColisLibreBtn" class="btn btn-secondary"><?php echo $langs->trans('NewFreePackage'); ?></button>
				<button id="saveColisBtn" class="btn btn-success"><?php echo $langs->trans('Save'); ?></button>
				<button id="loadColisBtn" class="btn btn-info"><?php echo $langs->trans('Load'); ?></button>
				<button onclick="preparePrint()" class="btn btn-warning"><?php echo $langs->trans('Print'); ?></button>
			</div>
		</div>

		<div class="main-content">
			<div class="left-panel">
				<div class="panel-header">
					<h2><?php echo $langs->trans('AvailableProducts'); ?></h2>
				</div>
				<div id="productInventory" class="product-inventory"></div>
			</div>

			<div class="right-panel">
				<div class="panel-header">
					<h2><?php echo $langs->trans('PackagesOverview'); ?></h2>
				</div>
				<div id="colisOverview" class="colis-overview"></div>
			</div>
		</div>

		<!-- Console de debug -->
		<div id="debugConsole" class="debug-console" style="display: none;">
			<h3>Console de Debug</h3>
			<div id="debugMessages"></div>
			<button onclick="clearDebug()" class="btn btn-sm">Effacer</button>
		</div>
	</div>

	<!-- Modal pour colis libre -->
	<div id="colisLibreModal" class="modal">
		<div class="modal-content">
			<div class="modal-header">
				<h3><?php echo $langs->trans('NewFreePackage'); ?></h3>
			</div>
			<div class="modal-body">
				<div class="form-group">
					<label for="colisLibreMaxWeight"><?php echo $langs->trans('MaxWeight'); ?> (kg):</label>
					<input type="number" id="colisLibreMaxWeight" value="25" min="1" max="50">
				</div>
				<div class="form-group">
					<label for="colisLibreMultiple"><?php echo $langs->trans('Multiple'); ?>:</label>
					<input type="number" id="colisLibreMultiple" value="1" min="1" max="100">
				</div>
				<div class="form-group">
					<h4><?php echo $langs->trans('Products'); ?>:</h4>
					<div id="colisLibreProducts">
						<div class="libre-product-item">
							<input type="text" placeholder="<?php echo $langs->trans('ProductName'); ?>" class="libre-product-name">
							<input type="text" placeholder="<?php echo $langs->trans('Description'); ?>" class="libre-product-description">
							<input type="number" placeholder="<?php echo $langs->trans('Quantity'); ?>" class="libre-product-quantity" min="1">
							<input type="number" placeholder="<?php echo $langs->trans('Weight'); ?> (kg)" class="libre-product-weight" step="0.1" min="0.1">
							<button type="button" class="btn btn-sm btn-danger remove-libre-product">×</button>
						</div>
					</div>
					<button type="button" id="addColisLibreItem" class="btn btn-sm btn-secondary"><?php echo $langs->trans('AddProduct'); ?></button>
				</div>
			</div>
			<div class="modal-footer">
				<button id="colisLibreOk" class="btn btn-primary"><?php echo $langs->trans('Create'); ?></button>
				<button id="colisLibreCancel" class="btn btn-secondary"><?php echo $langs->trans('Cancel'); ?></button>
			</div>
		</div>
	</div>

	<script>
		// Configuration globale
		const COMMANDE_ID = <?php echo $object->id; ?>;
		const COMMANDE_REF = '<?php echo $object->ref; ?>';
		
		// Variables globales
		let productsData = [];
		let colisData = [];
		let currentProductGroup = 'all';
		let currentSort = 'ref';
		let nextColisNumber = 1;

		// Fonctions utilitaires
		function debugLog(message) {
			console.log(`[FicheProduction] ${message}`);
			const debugConsole = document.getElementById('debugMessages');
			if (debugConsole) {
				const timestamp = new Date().toLocaleTimeString();
				debugConsole.innerHTML += `<div class="debug-message">[${timestamp}] ${message}</div>`;
				debugConsole.scrollTop = debugConsole.scrollHeight;
			}
		}

		function clearDebug() {
			const debugConsole = document.getElementById('debugMessages');
			if (debugConsole) {
				debugConsole.innerHTML = '';
			}
		}

		// Chargement des données initiales
		async function loadData() {
			debugLog('Chargement des données...');
			try {
				// Charger les produits de la commande
				const response = await fetch(`${window.location.pathname}?id=${COMMANDE_ID}&action=get_order_products&ajax=1`);
				if (response.ok) {
					productsData = await response.json();
					debugLog(`${productsData.length} produits chargés`);
				}
				
				// Charger les données de colisage existantes
				await loadColisage();
				
				// Mettre à jour l'affichage
				renderInventory();
				renderColisOverview();
				updateSummaryTotals();
				
			} catch (error) {
				debugLog(`Erreur lors du chargement: ${error.message}`);
			}
		}

		// Sauvegarde du colisage
		async function saveColisage() {
			debugLog('Sauvegarde du colisage...');
			
			if (colisData.length === 0) {
				alert('Aucun colis à sauvegarder');
				return;
			}

			try {
				const response = await fetch(`${window.location.pathname}?id=${COMMANDE_ID}&action=save_colisage&ajax=1`, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					body: `colisData=${encodeURIComponent(JSON.stringify(colisData))}`
				});

				if (response.ok) {
					const result = await response.json();
					if (result.success) {
						debugLog(`Colisage sauvegardé: ${result.message}`);
						alert(`Succès: ${result.message}`);
					} else {
						debugLog(`Erreur sauvegarde: ${result.message}`);
						alert(`Erreur: ${result.message}`);
					}
				} else {
					throw new Error('Erreur réseau lors de la sauvegarde');
				}
			} catch (error) {
				debugLog(`Erreur sauvegarde: ${error.message}`);
				alert(`Erreur lors de la sauvegarde: ${error.message}`);
			}
		}

		// Chargement du colisage
		async function loadColisage() {
			debugLog('Chargement du colisage...');
			
			try {
				const response = await fetch(`${window.location.pathname}?id=${COMMANDE_ID}&action=load_colisage&ajax=1`);
				
				if (response.ok) {
					const result = await response.json();
					if (result.success && result.colis) {
						colisData = result.colis;
						
						// Calculer le prochain numéro de colis
						let maxNumber = 0;
						colisData.forEach(colis => {
							if (colis.number > maxNumber) {
								maxNumber = colis.number;
							}
						});
						nextColisNumber = maxNumber + 1;
						
						debugLog(`${colisData.length} colis chargés`);
						
						// Mettre à jour les quantités utilisées
						updateUsedQuantities();
						
						renderColisOverview();
						renderInventory();
						updateSummaryTotals();
						
						alert(`Colisage chargé: ${result.message}`);
					} else {
						debugLog(`Aucun colisage trouvé: ${result.message}`);
					}
				} else {
					throw new Error('Erreur réseau lors du chargement');
				}
			} catch (error) {
				debugLog(`Erreur chargement: ${error.message}`);
				alert(`Erreur lors du chargement: ${error.message}`);
			}
		}

		// Mise à jour des quantités utilisées
		function updateUsedQuantities() {
			// Réinitialiser toutes les quantités utilisées
			productsData.forEach(product => {
				product.used = 0;
			});

			// Calculer les quantités utilisées
			colisData.forEach(colis => {
				if (colis.products) {
					colis.products.forEach(product => {
						if (!product.isLibre) {
							const productData = productsData.find(p => p.id == product.productId);
							if (productData) {
								productData.used += product.quantity * (colis.multiple || 1);
							}
						}
					});
				}
			});
		}

		// Rendu de l'inventaire des produits
		function renderInventory() {
			const container = document.getElementById('productInventory');
			if (!container) return;

			let filteredProducts = [...productsData];

			// Filtrage par groupe
			if (currentProductGroup === 'assigned') {
				filteredProducts = filteredProducts.filter(p => p.used > 0);
			} else if (currentProductGroup === 'unassigned') {
				filteredProducts = filteredProducts.filter(p => p.used < p.total);
			}

			// Tri
			filteredProducts.sort((a, b) => {
				switch (currentSort) {
					case 'label':
						return a.label.localeCompare(b.label);
					case 'weight':
						return b.weight - a.weight;
					default:
						return a.ref.localeCompare(b.ref);
				}
			});

			let html = '';
			filteredProducts.forEach(product => {
				const remaining = product.total - product.used;
				const isComplete = remaining <= 0;
				
				html += `
					<div class="product-item ${isComplete ? 'complete' : ''}" data-product-id="${product.id}">
						<div class="product-header">
							<div class="product-info">
								<div class="product-ref">${product.ref}</div>
								<div class="product-label">${product.label}</div>
							</div>
							<div class="product-quantities">
								<span class="quantity-badge ${isComplete ? 'complete' : ''}">${product.used}/${product.total}</span>
							</div>
						</div>
						<div class="product-details">
							<div class="detail-item">
								<span class="label">Poids:</span>
								<span class="value">${product.weight} kg</span>
							</div>
							<div class="detail-item">
								<span class="label">Dimensions:</span>
								<span class="value">${product.length}×${product.width}×${product.height} cm</span>
							</div>
							<div class="detail-item">
								<span class="label">Couleur:</span>
								<span class="value">${product.color}</span>
							</div>
							<div class="detail-item">
								<span class="label">Restant:</span>
								<span class="value ${remaining <= 0 ? 'complete' : ''}">${remaining}</span>
							</div>
						</div>
					</div>
				`;
			});

			container.innerHTML = html;
		}

		// Rendu de l'aperçu des colis
		function renderColisOverview() {
			const container = document.getElementById('colisOverview');
			if (!container) return;

			if (colisData.length === 0) {
				container.innerHTML = `
					<div class="empty-state">
						<p>Aucun colis créé</p>
						<p>Utilisez le bouton "Nouveau Colis" pour commencer</p>
					</div>
				`;
				return;
			}

			let html = '';
			colisData.forEach((colis, index) => {
				let totalWeight = 0;
				let productsHtml = '';

				if (colis.products) {
					colis.products.forEach(product => {
						const productWeight = product.weight * product.quantity;
						totalWeight += productWeight;

						if (product.isLibre) {
							productsHtml += `
								<div class="colis-product libre-product">
									<span class="product-name">${product.name}</span>
									<span class="product-quantity">×${product.quantity}</span>
									<span class="product-weight">${productWeight.toFixed(2)} kg</span>
								</div>
							`;
						} else {
							const productData = productsData.find(p => p.id == product.productId);
							const productName = productData ? productData.label : 'Produit inconnu';
							
							productsHtml += `
								<div class="colis-product">
									<span class="product-name">${productName}</span>
									<span class="product-quantity">×${product.quantity}</span>
									<span class="product-weight">${productWeight.toFixed(2)} kg</span>
								</div>
							`;
						}
					});
				}

				const maxWeight = colis.maxWeight || 25;
				const multiple = colis.multiple || 1;
				const totalWeightWithMultiple = totalWeight * multiple;
				const maxWeightWithMultiple = maxWeight * multiple;
				const weightPercent = (totalWeight / maxWeight) * 100;
				const status = weightPercent > 100 ? 'overweight' : 
							  weightPercent > 90 ? 'warning' : 'ok';

				html += `
					<div class="colis-card ${status}" data-colis-index="${index}">
						<div class="colis-header">
							<h3>Colis ${colis.number}</h3>
							<div class="colis-actions">
								<button class="btn btn-sm btn-danger" onclick="deleteColis(${index})">Supprimer</button>
							</div>
						</div>
						<div class="colis-info">
							<div class="weight-indicator">
								<div class="weight-bar">
									<div class="weight-fill ${status}" style="width: ${Math.min(weightPercent, 100)}%"></div>
								</div>
								<span class="weight-text">${totalWeight.toFixed(2)} / ${maxWeight} kg</span>
							</div>
							<div class="colis-multiple">
								Multiple: ×${multiple} = ${totalWeightWithMultiple.toFixed(2)} kg total
							</div>
						</div>
						<div class="colis-products">
							${productsHtml}
						</div>
					</div>
				`;
			});

			container.innerHTML = html;
		}

		// Mise à jour des totaux du résumé
		function updateSummaryTotals() {
			let totalProducts = 0;
			let totalWeight = 0;

			productsData.forEach(product => {
				totalProducts += product.total;
				totalWeight += product.total * product.weight;
			});

			const totalProductsEl = document.getElementById('orderTotalProducts');
			const totalWeightEl = document.getElementById('orderTotalWeight');

			if (totalProductsEl) totalProductsEl.textContent = totalProducts;
			if (totalWeightEl) totalWeightEl.textContent = totalWeight.toFixed(2) + ' kg';
		}

		// Ajouter un nouveau colis
		function addNewColis() {
			const newColis = {
				number: nextColisNumber++,
				maxWeight: 25,
				totalWeight: 0,
				multiple: 1,
				status: 'ok',
				products: []
			};

			colisData.push(newColis);
			renderColisOverview();
			debugLog(`Nouveau colis ${newColis.number} créé`);
		}

		// Supprimer un colis
		function deleteColis(index) {
			if (confirm(`Voulez-vous vraiment supprimer le colis ${colisData[index].number} ?`)) {
				const deletedColis = colisData.splice(index, 1)[0];
				updateUsedQuantities();
				renderInventory();
				renderColisOverview();
				debugLog(`Colis ${deletedColis.number} supprimé`);
			}
		}

		// Afficher la modal pour colis libre
		function showColisLibreModal() {
			document.getElementById('colisLibreModal').classList.add('show');
		}

		// Ajouter un item produit libre
		function addColisLibreItem() {
			const container = document.getElementById('colisLibreProducts');
			const newItem = document.createElement('div');
			newItem.className = 'libre-product-item';
			newItem.innerHTML = `
				<input type="text" placeholder="Nom du produit" class="libre-product-name">
				<input type="text" placeholder="Description" class="libre-product-description">
				<input type="number" placeholder="Quantité" class="libre-product-quantity" min="1">
				<input type="number" placeholder="Poids (kg)" class="libre-product-weight" step="0.1" min="0.1">
				<button type="button" class="btn btn-sm btn-danger remove-libre-product">×</button>
			`;

			const removeBtn = newItem.querySelector('.remove-libre-product');
			removeBtn.addEventListener('click', () => {
				newItem.remove();
			});

			container.appendChild(newItem);
		}

		// Créer un colis libre
		async function createColisLibre() {
			const maxWeight = parseFloat(document.getElementById('colisLibreMaxWeight').value) || 25;
			const multiple = parseInt(document.getElementById('colisLibreMultiple').value) || 1;
			
			const productItems = document.querySelectorAll('#colisLibreProducts .libre-product-item');
			const products = [];

			for (let item of productItems) {
				const name = item.querySelector('.libre-product-name').value.trim();
				const description = item.querySelector('.libre-product-description').value.trim();
				const quantity = parseInt(item.querySelector('.libre-product-quantity').value) || 0;
				const weight = parseFloat(item.querySelector('.libre-product-weight').value) || 0;

				if (name && quantity > 0 && weight > 0) {
					products.push({
						isLibre: true,
						name: name,
						description: description,
						quantity: quantity,
						weight: weight
					});
				}
			}

			if (products.length === 0) {
				alert('Veuillez ajouter au moins un produit valide');
				return false;
			}

			const newColis = {
				number: nextColisNumber++,
				maxWeight: maxWeight,
				totalWeight: 0,
				multiple: multiple,
				status: 'ok',
				products: products
			};

			// Calculer le poids total
			products.forEach(product => {
				newColis.totalWeight += product.weight * product.quantity;
			});

			colisData.push(newColis);
			renderColisOverview();
			debugLog(`Nouveau colis libre ${newColis.number} créé avec ${products.length} produits`);

			// Réinitialiser le formulaire
			document.getElementById('colisLibreMaxWeight').value = 25;
			document.getElementById('colisLibreMultiple').value = 1;
			document.getElementById('colisLibreProducts').innerHTML = `
				<div class="libre-product-item">
					<input type="text" placeholder="Nom du produit" class="libre-product-name">
					<input type="text" placeholder="Description" class="libre-product-description">
					<input type="number" placeholder="Quantité" class="libre-product-quantity" min="1">
					<input type="number" placeholder="Poids (kg)" class="libre-product-weight" step="0.1" min="0.1">
					<button type="button" class="btn btn-sm btn-danger remove-libre-product">×</button>
				</div>
			`;

			return true;
		}

		// Configuration des event listeners
		function setupEventListeners() {
			// Recherche de produits
			const productSearch = document.getElementById('productSearch');
			if (productSearch) {
				productSearch.addEventListener('input', function(e) {
					const searchTerm = e.target.value.toLowerCase();
					const productItems = document.querySelectorAll('.product-item');
					
					productItems.forEach(item => {
						const text = item.textContent.toLowerCase();
						item.style.display = text.includes(searchTerm) ? 'block' : 'none';
					});
				});
			}

			// Sélecteur de groupe de produits
			const productGroupSelect = document.getElementById('productGroupSelect');
			if (productGroupSelect) {
				productGroupSelect.addEventListener('change', function(e) {
					currentProductGroup = e.target.value;
					debugLog(`Changement groupe produit: ${currentProductGroup}`);
					renderInventory();
				});
			}

			// Sélecteur de tri
			const sortSelect = document.getElementById('sortSelect');
			if (sortSelect) {
				sortSelect.addEventListener('change', function(e) {
					currentSort = e.target.value;
					debugLog(`Changement tri: ${currentSort}`);
					renderInventory();
				});
			}

			// Bouton Nouveau Colis
			const addNewColisBtn = document.getElementById('addNewColisBtn');
			if (addNewColisBtn) {
				addNewColisBtn.addEventListener('click', function(e) {
					e.preventDefault();
					debugLog('Bouton nouveau colis cliqué');
					addNewColis();
				});
			}

			// Bouton Nouveau Colis Libre
			const addNewColisLibreBtn = document.getElementById('addNewColisLibreBtn');
			if (addNewColisLibreBtn) {
				addNewColisLibreBtn.addEventListener('click', function(e) {
					e.preventDefault();
					debugLog('Bouton nouveau colis libre cliqué');
					showColisLibreModal();
				});
			}

			// Bouton Sauvegarder
			const saveColisBtn = document.getElementById('saveColisBtn');
			if (saveColisBtn) {
				saveColisBtn.addEventListener('click', function(e) {
					e.preventDefault();
					debugLog('Bouton sauvegarder cliqué');
					saveColisage();
				});
			}

			// Bouton Charger
			const loadColisBtn = document.getElementById('loadColisBtn');
			if (loadColisBtn) {
				loadColisBtn.addEventListener('click', function(e) {
					e.preventDefault();
					debugLog('Bouton charger cliqué');
					loadColisage();
				});
			}

			// Event listeners pour la modale colis libre
			const colisLibreOk = document.getElementById('colisLibreOk');
			const colisLibreCancel = document.getElementById('colisLibreCancel');
			const addColisLibreItemBtn = document.getElementById('addColisLibreItem');

			if (colisLibreOk) {
				colisLibreOk.addEventListener('click', async () => {
					const success = await createColisLibre();
					if (success) {
						document.getElementById('colisLibreModal').classList.remove('show');
					}
				});
			}

			if (colisLibreCancel) {
				colisLibreCancel.addEventListener('click', () => {
					document.getElementById('colisLibreModal').classList.remove('show');
				});
			}

			if (addColisLibreItemBtn) {
				addColisLibreItemBtn.addEventListener('click', addColisLibreItem);
			}

			// Affichage/masquage de la console de debug (double-clic sur le titre)
			const header = document.querySelector('.header h1');
			if (header) {
				header.addEventListener('dblclick', function() {
					const debugConsole = document.getElementById('debugConsole');
					if (debugConsole) {
						debugConsole.style.display = debugConsole.style.display === 'none' ? 'block' : 'none';
					}
				});
			}
			
			debugLog('Event listeners configurés');
		}

		// Script pour la fonction d'impression
		function preparePrint() {
			// Sauvegarde l'état actuel de la page
			var originalTitle = document.title;
			
			// Modifie le titre pour l'impression
			document.title = 'Fiche de Production - <?php echo $object->ref; ?>';
			
			// Lance l'impression
			window.print();
			
			// Restaure le titre original après l'impression
			setTimeout(function() {
				document.title = originalTitle;
			}, 1000);
		}

		// Initialisation
		document.addEventListener('DOMContentLoaded', function() {
			debugLog('DOM chargé, initialisation...');
			debugLog('🆕 NOUVEAU : Fonctionnalité complète de sauvegarde/chargement implémentée !');
			debugLog('🆕 NOUVEAU : Support des produits libres en base de données !');
			debugLog('📋 NOUVEAU : Tableau récapitulatif des informations de commande ajouté !');
			
			renderInventory();
			renderColisOverview();
			setupEventListeners();
			loadData();
			updateSummaryTotals(); // Initialiser les totaux
			
			debugLog('Initialisation terminée');
			debugLog('Double-cliquez sur le titre pour afficher/masquer cette console');
		});
	</script>

	<?php
} else {
	// Afficher un message d'erreur si aucune commande n'est sélectionnée
	print '<div class="error">Aucune commande sélectionnée</div>';
}

print '</div>'; // End fichecenter
print dol_get_fiche_end();

llxFooter();
$db->close();
?>